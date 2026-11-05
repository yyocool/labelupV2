<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\LegalDocumentRepository;
use RuntimeException;

final class LegalDocumentService
{
    public const COMPANY_KEY = 'company';

    private const ALLOWED_KEYS = ['terms', 'privacy', 'marketing', self::COMPANY_KEY];

    private LegalDocumentRepository $repo;

    public function __construct()
    {
        $this->repo = new LegalDocumentRepository();
    }

    public function get(string $docKey): array
    {
        $this->assertKey($docKey);
        $row = $this->repo->findByKey($docKey);
        if (!$row && $docKey === self::COMPANY_KEY) {
            $this->ensureCompanyDocument();
            $row = $this->repo->findByKey($docKey);
        }
        if (!$row) {
            throw new RuntimeException('약관 문서를 찾을 수 없습니다.');
        }
        return $this->sanitize($row);
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $this->ensureCompanyDocument();
        return array_map(fn (array $row) => $this->sanitize($row), $this->repo->all());
    }

    public function update(string $docKey, string $title, string $content): array
    {
        $this->assertKey($docKey);
        $title = trim($title);
        $content = trim($content);
        if ($title === '') {
            throw new RuntimeException('제목을 입력해주세요.');
        }
        if ($content === '') {
            throw new RuntimeException('내용을 입력해주세요.');
        }
        if ($docKey === self::COMPANY_KEY) {
            $this->ensureCompanyDocument();
        }
        if (!$this->repo->findByKey($docKey)) {
            throw new RuntimeException('약관 문서를 찾을 수 없습니다.');
        }
        $this->repo->update($docKey, $title, $content);
        return $this->get($docKey);
    }

    private function ensureCompanyDocument(): void
    {
        if ($this->repo->findByKey(self::COMPANY_KEY)) {
            return;
        }
        $this->repo->insertIfMissing(self::COMPANY_KEY, '회사소개', self::companyDraft(), false);
    }

    private static function companyDraft(): string
    {
        return <<<'HTML'
<h2>세상의 가치를 발견하고, 모두의 일상을 풍요롭게</h2>
<p>업튜브 주식회사는 분야별 브랜드의 프로페셔널리티와 오리지널리티를 바탕으로 새로운 가치를 보여주는 <strong>밸류업 커머스 기업</strong>입니다.</p>
<p>식물, 패션, 그리고 수많은 소비재까지. 우리를 둘러싼 다양한 브랜드 가운데서 그 속에 잠재된 가치를 발견하고, 창의적이고 독창적인 아이디어로 브랜드가 더 나은 가능성으로 나아갈 수 있도록 솔루션을 그려갑니다.</p>

<h3>UP TO THE HIGH, BEYOND THE POSSIBILITY — 가능성 너머 저 높이</h3>
<p>업튜브는 가능성을 실현하는 도전의 장입니다. 함께하는 모두의 개성을 존중하고 그 안에서 독창성을 이끌어냅니다. 독창성에서 나온 각자의 전문성은 또 다른 새로운 가치를 만들어냅니다. 모두가 저마다의 가치를 빛내는 세상, 업튜브가 지향하는 미래입니다.</p>

<h3>OUR BUSINESS — Rediscovery Of Value</h3>
<ul>
<li><strong>기업 상거래를 넘어서</strong> : 제품 개발부터 커머스, 마케팅, 판매, 고객에게 닿기까지의 모든 경험이 우리의 미션입니다.</li>
<li><strong>새로운 생태계</strong> : 좋은 아이디어와 아이템을 지닌 기업을 발굴하여 고객과의 관계를 이어갑니다.</li>
<li><strong>글로벌 도전</strong> : 새로운 아이디어와 비즈니스 모델로 글로벌 기업으로 나아갑니다.</li>
</ul>

<h3>OUR BRANDS</h3>
<p>패션, 라이프스타일 각 분야에서 차별화된 경쟁력과 오리지널리티를 가진 브랜드가 업튜브와 함께 성장하고 있습니다.</p>
<ul>
<li><strong>미니가든</strong> — 내 안의 힐링 가든</li>
<li><strong>러블리힙</strong> — 사랑스럽게 힙하게</li>
<li><strong>세임페이지</strong> — 타임리스 스트릿</li>
<li><strong>라벨업(LABEL UP)</strong> — AI 디자이너 라비와 함께 라벨 디자인부터 인쇄·구매까지 한곳에서 해결하는 라벨 플랫폼</li>
</ul>

<h3>숫자로 보는 업튜브</h3>
<ul>
<li>우리 브랜드를 좋아하는 사람들 <strong>167,060+</strong></li>
<li>누적 판매량 <strong>171,624+</strong></li>
<li>브랜드 성장률 <strong>1,321%</strong></li>
<li>상품 좋아요 수 <strong>1,695,889+</strong></li>
</ul>

<h3>라벨업이 만드는 가치</h3>
<p>라벨업은 업튜브가 브랜드를 키우며 쌓아온 제품·커머스 경험을 바탕으로 만든 서비스입니다. 작은 가게와 1인 브랜드도 전문가 수준의 라벨을 쉽고 빠르게 만들 수 있도록, AI 디자인 도구와 템플릿, 인쇄 주문까지 하나로 연결합니다.</p>

<h3>회사 정보</h3>
<ul>
<li>상호 : 업튜브 주식회사 (UPTUBE Co., Ltd.)</li>
<li>대표이사 : 강민구</li>
<li>주소 : 서울 금천구 가산디지털1로 84, 1702호·1703호 (가산동, 에이스하이엔드타워8차)</li>
<li>대표전화 : 02-6956-5511</li>
<li>이메일 : info@uptube.kr</li>
</ul>

<h3>함께하고 싶으신가요?</h3>
<p>업튜브는 세상의 모든 가치를 발견할 가능성을 언제나 환영합니다. 비즈니스 제안 및 협업 문의는 <a href="mailto:info@uptube.kr">info@uptube.kr</a> 로 보내주세요.</p>
HTML;
    }

    private function assertKey(string $docKey): void
    {
        if (!in_array($docKey, self::ALLOWED_KEYS, true)) {
            throw new RuntimeException('유효하지 않은 문서 키입니다.');
        }
    }

    private function sanitize(array $row): array
    {
        return [
            'doc_key' => $row['doc_key'],
            'title' => $row['title'],
            'content' => $row['content'],
            'version' => (int) ($row['version'] ?? 1),
            'is_required' => (bool) ($row['is_required'] ?? true),
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }
}
