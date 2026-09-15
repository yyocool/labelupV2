<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;

/**
 * 변환 문서의 윈도우 글꼴을 이 PC(서버가 Windows일 때) Fonts 폴더에서 읽어 준다.
 * 파일 이름은 화이트리스트만 허용한다.
 */
final class EditorSystemFontApiController extends BaseController
{
    private const MaxBytes = 24 * 1024 * 1024;

    /** @var array<string, list<string>> */
    private const Files = [
        '맑은 고딕' => ['malgun.ttf', 'malgunbd.ttf'],
        'malgun gothic' => ['malgun.ttf', 'malgunbd.ttf'],
        '굴림' => ['gulim.ttc', 'gulim.ttf', 'gulimche.ttf'],
        'gulim' => ['gulim.ttc', 'gulim.ttf', 'gulimche.ttf'],
        '돋움' => ['gulim.ttc', 'dotum.ttc', 'dotum.ttf'],
        '돋움체' => ['gulim.ttc', 'dotum.ttc', 'dotum.ttf'],
        'dotum' => ['gulim.ttc', 'dotum.ttc', 'dotum.ttf'],
        'dotumche' => ['gulim.ttc', 'dotum.ttc', 'dotum.ttf'],
        '바탕' => ['batang.ttc', 'batang.ttf'],
        'batang' => ['batang.ttc', 'batang.ttf'],
        '궁서' => ['gungsuh.ttf', 'gungseh.ttf', 'batang.ttc'],
        'gungsuh' => ['gungsuh.ttf', 'gungseh.ttf'],
        'arial' => ['arial.ttf', 'arialbd.ttf', 'ariali.ttf', 'arialbi.ttf'],
        'arial narrow' => ['arialn.ttf', 'arialnb.ttf', 'arialni.ttf', 'arialnbi.ttf'],
        'times new roman' => ['times.ttf', 'timesbd.ttf', 'timesi.ttf', 'timesbi.ttf'],
        'georgia' => ['georgia.ttf', 'georgiab.ttf', 'georgiai.ttf', 'georgiaz.ttf'],
        'calibri' => ['calibri.ttf', 'calibrib.ttf', 'calibrii.ttf', 'calibriz.ttf'],
        'candara' => ['candara.ttf', 'candarab.ttf', 'candarai.ttf', 'candaraz.ttf'],
        'tahoma' => ['tahoma.ttf', 'tahomabd.ttf'],
        'verdana' => ['verdana.ttf', 'verdanab.ttf', 'verdanai.ttf', 'verdanaz.ttf'],
        'courier new' => ['cour.ttf', 'courbd.ttf', 'couri.ttf', 'courbi.ttf'],
        '휴먼편지체' => ['hymmmo.ttf', 'hywulm.ttf'],
    ];

    public function show(): never
    {
        $family = trim((string) ($_GET['family'] ?? ''));
        $style = strtolower(trim((string) ($_GET['style'] ?? 'regular')));
        if ($family === '') {
            $this->jsonError('글꼴 이름이 없습니다.', null, 400);
        }

        $files = $this->filesFor($family);
        if ($files === []) {
            $this->jsonError('지원하지 않는 글꼴입니다.', ['family' => $family], 404);
        }

        $ordered = $this->orderByStyle($files, $style);
        foreach ($ordered as $name) {
            foreach ($this->fileCandidates($name) as $path) {
                // Fonts는 특수 폴더라 is_dir이 실패해도 filesize/readfile은 되는 경우가 많다.
                $size = @filesize($path);
                if (!is_int($size) || $size < 100 || $size > self::MaxBytes) {
                    continue;
                }
                $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
                header('Content-Type: ' . ($ext === 'ttc' ? 'font/collection' : 'font/ttf'));
                header('Content-Disposition: inline; filename="' . $name . '"');
                header('Content-Length: ' . (string) $size);
                header('Cache-Control: private, max-age=3600');
                header('X-Accel-Buffering: no');
                @readfile($path);
                exit;
            }
        }

        $this->jsonError('이 PC에서 글꼴 파일을 찾지 못했습니다.', ['family' => $family], 404);
    }

    /** @return list<string> */
    private function filesFor(string $family): array
    {
        $key = strtolower(preg_replace('/\s+/', ' ', $family) ?? $family);
        foreach (self::Files as $name => $files) {
            if ($name === $family || strtolower($name) === $key) {
                return $files;
            }
        }
        return [];
    }

    /**
     * @param list<string> $files
     * @return list<string>
     */
    private function orderByStyle(array $files, string $style): array
    {
        $wantBold = str_contains($style, 'bold');
        $wantItalic = str_contains($style, 'italic');
        usort($files, static function (string $a, string $b) use ($wantBold, $wantItalic): int {
            return self::styleScore($b, $wantBold, $wantItalic) <=> self::styleScore($a, $wantBold, $wantItalic);
        });
        return $files;
    }

    private static function styleScore(string $file, bool $wantBold, bool $wantItalic): int
    {
        $n = strtolower($file);
        $bold = str_contains($n, 'bd') || str_contains($n, 'bold') || str_ends_with(pathinfo($n, PATHINFO_FILENAME), 'b');
        $italic = str_contains($n, 'i.ttf') || str_contains($n, 'italic') || str_contains($n, 'i.');
        $score = 0;
        if ($bold === $wantBold) {
            $score += 2;
        }
        if ($italic === $wantItalic) {
            $score += 2;
        }
        if (str_contains($n, 'sl') || str_contains($n, 'light') || str_contains($n, 'semilight')) {
            $score -= 4;
        }
        if ($n === 'malgun.ttf' && !$wantBold && !$wantItalic) {
            $score += 8;
        }
        return $score;
    }

    /** @return list<string> */
    private function fileCandidates(string $name): array
    {
        $dirs = [];
        $win = getenv('WINDIR');
        if (is_string($win) && $win !== '') {
            $dirs[] = rtrim($win, '\\/') . DIRECTORY_SEPARATOR . 'Fonts';
        }
        $dirs[] = 'C:' . DIRECTORY_SEPARATOR . 'Windows' . DIRECTORY_SEPARATOR . 'Fonts';
        $local = getenv('LOCALAPPDATA');
        if (is_string($local) && $local !== '') {
            $dirs[] = rtrim($local, '\\/') . DIRECTORY_SEPARATOR . 'Microsoft' . DIRECTORY_SEPARATOR . 'Windows' . DIRECTORY_SEPARATOR . 'Fonts';
        }
        $dirs[] = 'C:' . DIRECTORY_SEPARATOR . 'LabelupData' . DIRECTORY_SEPARATOR . 'fonts';
        $paths = [];
        foreach (array_unique($dirs) as $dir) {
            $paths[] = $dir . DIRECTORY_SEPARATOR . $name;
        }
        return $paths;
    }
}
