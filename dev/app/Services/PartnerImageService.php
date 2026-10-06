<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PartnerImageRepository;
use RuntimeException;
use ZipArchive;

final class PartnerImageService
{
    public const MAX_DOWNLOAD = 100;

    private PartnerImageRepository $images;

    public function __construct()
    {
        $this->images = new PartnerImageRepository();
    }

    /**
     * @param array{q?:string,category_id?:int,product_status?:string} $filters
     */
    public function catalog(array $filters, int $page = 1): array
    {
        return $this->images->catalog($filters, $page, 20);
    }

    /**
     * @param array<int, int> $ids
     * @param array{q?:string,category_id?:int,product_status?:string} $filters
     */
    public function streamZip(array $ids, bool $useFilters, array $filters): void
    {
        if ($useFilters) {
            $ids = $this->images->idsForFilters($filters, self::MAX_DOWNLOAD + 1);
            if (count($ids) > self::MAX_DOWNLOAD) {
                throw new RuntimeException('검색 결과가 ' . self::MAX_DOWNLOAD . '개를 넘습니다. 카테고리나 상품명으로 범위를 줄여 주세요.');
            }
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            throw new RuntimeException($useFilters ? '검색 결과가 없습니다.' : '받을 상품을 선택해주세요.');
        }
        if (count($ids) > self::MAX_DOWNLOAD) {
            throw new RuntimeException('한 번에 ' . self::MAX_DOWNLOAD . '개까지 받을 수 있습니다.');
        }
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('압축 다운로드를 사용할 수 없습니다.');
        }

        $rows = $this->images->findSellableByIds($ids);
        $detailPages = new ProductDetailPageService();
        if (count($rows) === 1) {
            $detailPages->streamImageZip((int) $rows[0]['id']);
        }
        $bundles = [];
        foreach ($rows as $row) {
            $files = $detailPages->imageFiles((int) $row['id']);
            if ($files !== []) {
                $bundles[] = ['sku' => (string) ($row['sku'] ?? ''), 'id' => (int) $row['id'], 'files' => $files];
            }
        }
        if ($bundles === []) {
            throw new RuntimeException('받을 수 있는 이미지가 없습니다.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'ptimg');
        if ($tmp === false) {
            throw new RuntimeException('다운로드 파일을 만들지 못했습니다.');
        }
        $zipPath = $tmp . '.zip';
        @unlink($tmp);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new RuntimeException('다운로드 파일을 만들지 못했습니다.');
        }
        $flags = defined('ZipArchive::FL_ENC_UTF_8') ? ZipArchive::FL_ENC_UTF_8 : 0;
        foreach ($bundles as $bundle) {
            $folder = $this->folderName($bundle['sku'], $bundle['id']);
            foreach ($bundle['files'] as $file) {
                $zip->addFile($file['path'], $folder . '/' . $file['name'], 0, 0, $flags);
            }
        }
        $zip->close();

        $downloadName = 'labelup-images-' . date('Ymd-His') . '.zip';

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $downloadName . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
        header('Content-Length: ' . (string) filesize($zipPath));
        header('Cache-Control: no-store');
        readfile($zipPath);
        @unlink($zipPath);
        exit;
    }

    private function folderName(string $sku, int $id): string
    {
        $sku = trim($sku);
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '', $sku) ?? '';
        return $safe !== '' ? $safe : ('product-' . $id);
    }
}
