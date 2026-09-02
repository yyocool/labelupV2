<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;
use SimpleXMLElement;

final class OfficeDocumentParser
{
    public const MAX_ROWS = 200;
    public const MAX_COLS = 20;
    public const MAX_BYTES = 3_500_000;

    /**
     * @param array<int, array{role:string, content:mixed}> $messages
     * @return ?array{source_name:string, source_kind:string, columns:array<int,string>, rows:array<int,array<int,string>>, summary:string}
     */
    public function extractFromMessages(array $messages): ?array
    {
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') !== 'user') {
                continue;
            }
            $content = $messages[$i]['content'] ?? '';
            if (is_array($content)) {
                foreach ($content as $part) {
                    if (!is_array($part)) {
                        continue;
                    }
                    $type = (string) ($part['type'] ?? '');
                    if ($type === 'file') {
                        $name = (string) ($part['name'] ?? $part['file']['name'] ?? '첨부파일');
                        $url = (string) ($part['file']['url'] ?? $part['data_url'] ?? $part['url'] ?? '');
                        $sheet = $this->parseDataUrl($name, $url);
                        if ($sheet !== null) {
                            return $sheet;
                        }
                    }
                    if ($type === 'text') {
                        $sheet = $this->tryParseEmbeddedText((string) ($part['text'] ?? ''));
                        if ($sheet !== null) {
                            return $sheet;
                        }
                    }
                }
            } elseif (is_string($content)) {
                $sheet = $this->tryParseEmbeddedText($content);
                if ($sheet !== null) {
                    return $sheet;
                }
            }
        }

        return null;
    }

    public function messagesHaveOffice(array $messages): bool
    {
        return $this->extractFromMessages($messages) !== null
            || $this->messagesHaveOfficePart($messages);
    }

    /** @param array<int, array{role:string, content:mixed}> $messages */
    public function messagesHaveOfficePart(array $messages): bool
    {
        foreach ($messages as $item) {
            $content = $item['content'] ?? null;
            if (!is_array($content)) {
                continue;
            }
            foreach ($content as $part) {
                if (!is_array($part) || ($part['type'] ?? '') !== 'file') {
                    continue;
                }
                $name = strtolower((string) ($part['name'] ?? $part['file']['name'] ?? ''));
                if (preg_match('/\.(xlsx|xls|csv|docx|doc|tsv)$/', $name)) {
                    return true;
                }
            }
        }
        return false;
    }

    /** @return ?array{source_name:string, source_kind:string, columns:array<int,string>, rows:array<int,array<int,string>>, summary:string} */
    public function parseDataUrl(string $name, string $dataUrl): ?array
    {
        $dataUrl = trim($dataUrl);
        if ($dataUrl === '' || !preg_match('#^data:[^;]+;base64,([A-Za-z0-9+/=\s]+)$#', $dataUrl, $m)) {
            return null;
        }
        $bin = base64_decode(preg_replace('/\s+/', '', $m[1]) ?? '', true);
        if (!is_string($bin) || $bin === '') {
            throw new RuntimeException('첨부 파일을 읽을 수 없습니다.');
        }
        if (strlen($bin) > self::MAX_BYTES) {
            throw new RuntimeException('첨부 파일이 너무 큽니다. 3MB 이하로 올려 주세요.');
        }

        return $this->parseBytes($name, $bin);
    }

    /** @return array{source_name:string, source_kind:string, columns:array<int,string>, rows:array<int,array<int,string>>, summary:string} */
    public function parseBytes(string $name, string $bin): array
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $sheet = match ($ext) {
            'csv', 'tsv', 'txt' => $this->parseCsv($name, $bin, $ext === 'tsv' ? "\t" : ','),
            'xlsx' => $this->parseXlsx($name, $bin),
            'xls' => $this->parseXls($name, $bin),
            'docx' => $this->parseDocx($name, $bin),
            'doc' => throw new RuntimeException('구버전 Word(.doc)는 지원하지 않습니다. .docx로 저장해 다시 첨부해 주세요.'),
            default => throw new RuntimeException('엑셀(.xlsx/.csv) 또는 워드(.docx) 파일만 분석할 수 있습니다.'),
        };

        return $this->finalize($sheet);
    }

    /** @return ?array{source_name:string, source_kind:string, columns:array<int,string>, rows:array<int,array<int,string>>, summary:string} */
    private function tryParseEmbeddedText(string $text): ?array
    {
        if (!preg_match('/\[첨부:\s*([^\]\n]+\.(csv|tsv|txt))\]\s*\n([\s\S]+)/u', $text, $m)) {
            if (!preg_match('/\A[^\n]{1,80}[,;\t][^\n]+\n[^\n]+[,;\t]/u', $text)) {
                return null;
            }
            $body = $text;
            $name = '첨부.csv';
        } else {
            $name = trim($m[1]);
            $body = trim($m[3]);
        }
        $delim = str_contains($body, "\t") ? "\t" : ',';
        try {
            return $this->finalize($this->parseCsvText($name, $body, $delim));
        } catch (RuntimeException) {
            return null;
        }
    }

    /**
     * @return array{source_name:string, source_kind:string, columns:array<int,string>, rows:array<int,array<int,string>>}
     */
    private function parseCsv(string $name, string $bin, string $delim): array
    {
        return $this->parseCsvText($name, $this->decodeText($bin), $delim);
    }

    /**
     * @return array{source_name:string, source_kind:string, columns:array<int,string>, rows:array<int,array<int,string>>}
     */
    private function parseCsvText(string $name, string $text, string $delim): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = preg_split("/\n/", $text) ?: [];
        $lines = array_values(array_filter($lines, static fn($l) => trim((string) $l) !== ''));
        if ($lines === []) {
            throw new RuntimeException('빈 스프레드시트입니다.');
        }
        $grid = [];
        foreach ($lines as $line) {
            $grid[] = str_getcsv($line, $delim);
        }
        return $this->gridToSheet($name, 'csv', $grid);
    }

    /**
     * @return array{source_name:string, source_kind:string, columns:array<int,string>, rows:array<int,array<int,string>>}
     */
    private function parseXls(string $name, string $bin): array
    {
        if (str_starts_with($bin, 'PK')) {
            return $this->parseXlsx($name, $bin);
        }
        throw new RuntimeException('구버전 Excel(.xls)은 지원하지 않습니다. .xlsx 또는 .csv로 저장해 다시 첨부해 주세요.');
    }

    /**
     * @return array{source_name:string, source_kind:string, columns:array<int,string>, rows:array<int,array<int,string>>}
     */
    private function parseXlsx(string $name, string $bin): array
    {
        $zip = $this->openZip($bin, '엑셀');
        $strings = $this->xlsxSharedStrings($zip);
        $sheetPath = $this->xlsxFirstSheetPath($zip);
        $xml = $zip->get($sheetPath);
        $zip->close();
        if (!is_string($xml) || $xml === '') {
            throw new RuntimeException('엑셀 시트를 읽지 못했습니다.');
        }

        $sheet = @simplexml_load_string($xml);
        if (!$sheet instanceof SimpleXMLElement) {
            throw new RuntimeException('엑셀 시트 XML을 해석하지 못했습니다.');
        }
        $sheet->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rows = $sheet->xpath('.//m:sheetData/m:row') ?: [];
        $grid = [];
        foreach ($rows as $row) {
            $line = [];
            foreach ($row->c ?? [] as $cell) {
                $ref = (string) ($cell['r'] ?? '');
                $col = $this->colIndexFromRef($ref);
                if ($col < 0) {
                    $col = count($line);
                }
                while (count($line) < $col) {
                    $line[] = '';
                }
                $line[$col] = $this->xlsxCellValue($cell, $strings);
            }
            if ($line !== [] && !self::rowEmpty($line)) {
                $grid[] = $line;
            }
        }
        if ($grid === []) {
            throw new RuntimeException('엑셀에 읽을 수 있는 표가 없습니다.');
        }

        return $this->gridToSheet($name, 'xlsx', $grid);
    }

    /**
     * @return array{source_name:string, source_kind:string, columns:array<int,string>, rows:array<int,array<int,string>>}
     */
    private function parseDocx(string $name, string $bin): array
    {
        $zip = $this->openZip($bin, '워드');
        $xml = $zip->get('word/document.xml');
        $zip->close();
        if (!is_string($xml) || $xml === '') {
            throw new RuntimeException('워드 본문을 읽지 못했습니다.');
        }
        $doc = @simplexml_load_string($xml);
        if (!$doc instanceof SimpleXMLElement) {
            throw new RuntimeException('워드 문서를 해석하지 못했습니다.');
        }
        $doc->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $tables = $doc->xpath('//w:tbl') ?: [];
        foreach ($tables as $table) {
            $grid = [];
            foreach ($table->xpath('.//w:tr') ?: [] as $tr) {
                $line = [];
                foreach ($tr->xpath('./w:tc') ?: [] as $tc) {
                    $texts = $tc->xpath('.//w:t') ?: [];
                    $cell = '';
                    foreach ($texts as $t) {
                        $cell .= (string) $t;
                    }
                    $line[] = trim($cell);
                }
                if ($line !== [] && !self::rowEmpty($line)) {
                    $grid[] = $line;
                }
            }
            if (count($grid) >= 2 && count($grid[0]) >= 2) {
                return $this->gridToSheet($name, 'docx', $grid);
            }
        }

        $paras = [];
        foreach ($doc->xpath('//w:p') ?: [] as $p) {
            $texts = $p->xpath('.//w:t') ?: [];
            $line = '';
            foreach ($texts as $t) {
                $line .= (string) $t;
            }
            $line = trim($line);
            if ($line !== '') {
                $paras[] = $line;
            }
        }
        if ($paras === []) {
            throw new RuntimeException('워드에서 표나 본문을 찾지 못했습니다.');
        }

        $kv = $this->paragraphsToKeyValue($paras);
        if ($kv !== null) {
            return $this->gridToSheet($name, 'docx', $kv);
        }

        $grid = [['내용']];
        foreach (array_slice($paras, 0, self::MAX_ROWS) as $line) {
            $grid[] = [$line];
        }
        return $this->gridToSheet($name, 'docx', $grid);
    }

    /**
     * @param array<int, string> $paras
     * @return ?array<int, array<int, string>>
     */
    private function paragraphsToKeyValue(array $paras): ?array
    {
        $pairs = [];
        foreach ($paras as $line) {
            if (!preg_match('/^(.{1,40})[:：]\s*(.+)$/u', $line, $m)) {
                continue;
            }
            $pairs[] = [trim($m[1]), trim($m[2])];
        }
        if (count($pairs) < 2) {
            return null;
        }
        $grid = [array_column($pairs, 0), array_column($pairs, 1)];
        return $grid;
    }

    /**
     * @param array<int, array<int, string>> $grid
     * @return array{source_name:string, source_kind:string, columns:array<int,string>, rows:array<int,array<int,string>>}
     */
    private function gridToSheet(string $name, string $kind, array $grid): array
    {
        $header = array_map(fn($v) => $this->normalizeHeader((string) $v), $grid[0] ?? []);
        if ($header === []) {
            throw new RuntimeException('표 제목 행을 찾지 못했습니다.');
        }
        $header = array_slice($header, 0, self::MAX_COLS);
        $used = [];
        foreach ($header as $i => $col) {
            $base = $col !== '' ? $col : ('열' . ($i + 1));
            $key = $base;
            $n = 2;
            while (isset($used[$key])) {
                $key = $base . $n;
                $n++;
            }
            $used[$key] = true;
            $header[$i] = $key;
        }

        $rows = [];
        foreach (array_slice($grid, 1, self::MAX_ROWS) as $line) {
            $cells = [];
            foreach ($header as $i => $_) {
                $cells[] = trim((string) ($line[$i] ?? ''));
            }
            if (self::rowEmpty($cells)) {
                continue;
            }
            $rows[] = $cells;
        }
        if ($rows === []) {
            throw new RuntimeException('데이터 행이 없습니다. 첫 행은 제목, 그 아래가 값이어야 합니다.');
        }

        return [
            'source_name' => $name,
            'source_kind' => $kind,
            'columns' => $header,
            'rows' => $rows,
        ];
    }

    /**
     * @param array{source_name:string, source_kind:string, columns:array<int,string>, rows:array<int,array<int,string>>} $sheet
     * @return array{source_name:string, source_kind:string, columns:array<int,string>, rows:array<int,array<int,string>>, summary:string}
     */
    private function finalize(array $sheet): array
    {
        $cols = $sheet['columns'];
        $rows = $sheet['rows'];
        $preview = array_slice($rows, 0, 6);
        $lines = ['| ' . implode(' | ', $cols) . ' |', '| ' . implode(' | ', array_fill(0, count($cols), '---')) . ' |'];
        foreach ($preview as $row) {
            $lines[] = '| ' . implode(' | ', $row) . ' |';
        }
        $sheet['summary'] = sprintf(
            "파일: %s\n열 %d개 · 행 %d개\n\n%s",
            $sheet['source_name'],
            count($cols),
            count($rows),
            implode("\n", $lines)
        );
        return $sheet;
    }

    private function openZip(string $bin, string $label): OfficeZipReader
    {
        try {
            return OfficeZipReader::fromBinary($bin);
        } catch (RuntimeException $e) {
            throw new RuntimeException($label . ' 파일을 열지 못했습니다. ' . $e->getMessage());
        }
    }

    /** @return array<int, string> */
    private function xlsxSharedStrings(OfficeZipReader $zip): array
    {
        $xml = $zip->get('xl/sharedStrings.xml');
        if (!is_string($xml) || $xml === '') {
            return [];
        }
        $sst = @simplexml_load_string($xml);
        if (!$sst instanceof SimpleXMLElement) {
            return [];
        }
        $sst->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $out = [];
        foreach ($sst->xpath('//m:si') ?: [] as $si) {
            $texts = $si->xpath('.//m:t') ?: [];
            $buf = '';
            foreach ($texts as $t) {
                $buf .= (string) $t;
            }
            $out[] = $buf;
        }
        return $out;
    }

    private function xlsxFirstSheetPath(OfficeZipReader $zip): string
    {
        $workbook = $zip->get('xl/workbook.xml');
        if (is_string($workbook) && $workbook !== '') {
            $rels = $zip->get('xl/_rels/workbook.xml.rels');
            $wb = @simplexml_load_string($workbook);
            $relMap = [];
            if (is_string($rels) && $rels !== '') {
                $rx = @simplexml_load_string($rels);
                if ($rx instanceof SimpleXMLElement) {
                    foreach ($rx->Relationship ?? [] as $rel) {
                        $relMap[(string) $rel['Id']] = ltrim((string) $rel['Target'], '/');
                    }
                }
            }
            if ($wb instanceof SimpleXMLElement) {
                $wb->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                $wb->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                $sheets = $wb->xpath('//m:sheet') ?: [];
                if ($sheets !== []) {
                    $rid = (string) ($sheets[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'] ?? '');
                    if ($rid !== '' && isset($relMap[$rid])) {
                        $target = $relMap[$rid];
                        return str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
                    }
                }
            }
        }
        foreach (['xl/worksheets/sheet1.xml', 'xl/worksheets/sheet.xml'] as $path) {
            if ($zip->has($path)) {
                return $path;
            }
        }
        throw new RuntimeException('엑셀 시트를 찾지 못했습니다.');
    }

    /** @param array<int, string> $strings */
    private function xlsxCellValue(SimpleXMLElement $cell, array $strings): string
    {
        $type = (string) ($cell['t'] ?? '');
        $raw = trim((string) ($cell->v ?? ''));
        if ($type === 's') {
            $idx = (int) $raw;
            return trim((string) ($strings[$idx] ?? ''));
        }
        if ($type === 'inlineStr') {
            $texts = $cell->xpath('.//*[local-name()="t"]') ?: [];
            $buf = '';
            foreach ($texts as $t) {
                $buf .= (string) $t;
            }
            return trim($buf);
        }
        return $raw;
    }

    private function colIndexFromRef(string $ref): int
    {
        if (!preg_match('/^([A-Z]+)/i', $ref, $m)) {
            return -1;
        }
        $letters = strtoupper($m[1]);
        $n = 0;
        for ($i = 0; $i < strlen($letters); $i++) {
            $n = $n * 26 + (ord($letters[$i]) - 64);
        }
        return $n - 1;
    }

    private function normalizeHeader(string $raw): string
    {
        $s = trim($raw);
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
        return $s === '' ? '' : mb_substr($s, 0, 40);
    }

    /** @param array<int, string> $row */
    private static function rowEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }
        return true;
    }

    private function decodeText(string $bin): string
    {
        if (str_starts_with($bin, "\xEF\xBB\xBF")) {
            return substr($bin, 3);
        }
        $utf = @mb_convert_encoding($bin, 'UTF-8', 'UTF-8');
        if (is_string($utf) && $utf !== '' && !preg_match('/\x00/', $utf)) {
            return $utf;
        }
        $cp949 = @mb_convert_encoding($bin, 'UTF-8', 'CP949');
        return is_string($cp949) ? $cp949 : $bin;
    }
}

final class OfficeZipReader
{
    /** @var array<string, string> */
    private array $files = [];

    public static function fromBinary(string $bin): self
    {
        if (!str_starts_with($bin, "PK")) {
            throw new RuntimeException('압축 문서 형식이 아닙니다.');
        }
        $self = new self();
        if (class_exists(\ZipArchive::class)) {
            $self->loadWithZipArchive($bin);
        } else {
            $self->loadManual($bin);
        }
        if ($self->files === []) {
            throw new RuntimeException('문서 항목을 읽지 못했습니다.');
        }
        return $self;
    }

    public function get(string $name): ?string
    {
        $name = ltrim(str_replace('\\', '/', $name), '/');
        return $this->files[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return $this->get($name) !== null;
    }

    public function close(): void
    {
        $this->files = [];
    }

    private function loadWithZipArchive(string $bin): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'luoff_');
        if ($tmp === false || file_put_contents($tmp, $bin) === false) {
            $this->loadManual($bin);
            return;
        }
        $zip = new \ZipArchive();
        $ok = $zip->open($tmp);
        if ($ok !== true) {
            @unlink($tmp);
            $this->loadManual($bin);
            return;
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = is_array($stat) ? (string) ($stat['name'] ?? '') : '';
            if ($name === '' || str_ends_with($name, '/')) {
                continue;
            }
            $data = $zip->getFromIndex($i);
            if (is_string($data)) {
                $this->files[ltrim(str_replace('\\', '/', $name), '/')] = $data;
            }
        }
        $zip->close();
        @unlink($tmp);
    }

    private function loadManual(string $bin): void
    {
        $len = strlen($bin);
        $pos = 0;
        while ($pos + 30 <= $len && substr($bin, $pos, 4) === "PK\x03\x04") {
            $method = unpack('v', substr($bin, $pos + 8, 2))[1] ?? 0;
            $compSize = unpack('V', substr($bin, $pos + 18, 4))[1] ?? 0;
            $nameLen = unpack('v', substr($bin, $pos + 26, 2))[1] ?? 0;
            $extraLen = unpack('v', substr($bin, $pos + 28, 2))[1] ?? 0;
            $flags = unpack('v', substr($bin, $pos + 6, 2))[1] ?? 0;
            $name = substr($bin, $pos + 30, $nameLen);
            $dataStart = $pos + 30 + $nameLen + $extraLen;
            if ($dataStart > $len) {
                break;
            }
            if (($flags & 0x08) === 0x08 && $compSize === 0) {
                break;
            }
            $data = substr($bin, $dataStart, $compSize);
            $out = null;
            if ($method === 0) {
                $out = $data;
            } elseif ($method === 8) {
                $out = @gzinflate($data);
            }
            if (is_string($out) && $name !== '' && !str_ends_with($name, '/')) {
                $this->files[ltrim(str_replace('\\', '/', $name), '/')] = $out;
            }
            $pos = $dataStart + $compSize;
        }

        if ($this->files === []) {
            $this->loadFromCentralDirectory($bin);
        }
    }

    private function loadFromCentralDirectory(string $bin): void
    {
        $eocd = strrpos($bin, "PK\x05\x06");
        if ($eocd === false) {
            return;
        }
        $cdOffset = unpack('V', substr($bin, $eocd + 16, 4))[1] ?? 0;
        $cdCount = unpack('v', substr($bin, $eocd + 10, 2))[1] ?? 0;
        $pos = $cdOffset;
        $len = strlen($bin);
        for ($i = 0; $i < $cdCount && $pos + 46 <= $len; $i++) {
            if (substr($bin, $pos, 4) !== "PK\x01\x02") {
                break;
            }
            $method = unpack('v', substr($bin, $pos + 10, 2))[1] ?? 0;
            $compSize = unpack('V', substr($bin, $pos + 20, 4))[1] ?? 0;
            $nameLen = unpack('v', substr($bin, $pos + 28, 2))[1] ?? 0;
            $extraLen = unpack('v', substr($bin, $pos + 30, 2))[1] ?? 0;
            $commentLen = unpack('v', substr($bin, $pos + 32, 2))[1] ?? 0;
            $localOff = unpack('V', substr($bin, $pos + 42, 4))[1] ?? 0;
            $name = substr($bin, $pos + 46, $nameLen);
            $localNameLen = unpack('v', substr($bin, $localOff + 26, 2))[1] ?? 0;
            $localExtra = unpack('v', substr($bin, $localOff + 28, 2))[1] ?? 0;
            $dataStart = $localOff + 30 + $localNameLen + $localExtra;
            $data = substr($bin, $dataStart, $compSize);
            $out = $method === 0 ? $data : ($method === 8 ? @gzinflate($data) : null);
            if (is_string($out) && $name !== '' && !str_ends_with($name, '/')) {
                $this->files[ltrim(str_replace('\\', '/', $name), '/')] = $out;
            }
            $pos += 46 + $nameLen + $extraLen + $commentLen;
        }
    }
}
