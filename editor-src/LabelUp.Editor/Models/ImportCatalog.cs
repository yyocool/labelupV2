namespace LabelUp.Editor.Models;

public sealed record ImportCategory(string Id, string Label, int Count);
public sealed record ImportSpec(string Code, string Size, int Qty, string Shape, float WidthMm, float HeightMm);
public sealed record ImportDesign(string Title, string Tags, string Tone);
public sealed record ImportTemplateTag(string Tag, int Count, bool Active = false);
public sealed record ImportMyDesign(string Title, string Spec, string Date, string Tone, string? Badge = null);

public static class ImportCatalog
{
    public static readonly ImportCategory[] LabelCategories =
    [
        new("a4", "A4 라벨", 249),
        new("jet", "제트라벨", 198),
        new("theroll", "더롤라벨", 461),
        new("a3", "A3 라벨", 1)
    ];

    public static readonly ImportCategory[] TagCategories =
    [
        new("a4", "A4 태그", 78),
        new("jet", "제트태그", 7),
        new("theroll", "더롤태그", 6)
    ];

    public static readonly Dictionary<string, ImportSpec[]> LabelBlank = new()
    {
        ["a4"] =
        [
            new("100", "25×20 mm", 84, "heart", 25, 20),
            new("101", "15×15 mm", 15, "circle", 15, 15),
            new("102", "57.5×48 mm", 12, "star", 57.5f, 48),
            new("103", "25×25 mm", 21, "clover", 25, 25),
            new("105", "25×25 mm", 24, "speech", 25, 25),
            new("106", "25×25 mm", 20, "bone", 25, 25),
            new("111", "25×25 mm", 24, "arrow", 25, 25),
            new("112", "25×25 mm", 24, "cloud", 25, 25),
            new("113", "25×25 mm", 24, "hex", 25, 25),
            new("114", "25×25 mm", 24, "circle", 25, 25),
            new("115", "25×25 mm", 24, "star", 25, 25),
            new("116", "25×25 mm", 24, "heart", 25, 25)
        ],
        ["jet"] =
        [
            new("ZJ030010", "30×9.7 mm", 16, "rect", 30, 9.7f),
            new("ZJ030020", "40×15 mm", 12, "rect", 40, 15),
            new("ZJ030030", "50×20 mm", 10, "rect", 50, 20),
            new("ZJ030040", "60×25 mm", 8, "rect", 60, 25),
            new("ZJ030050", "70×30 mm", 6, "rect", 70, 30),
            new("ZJ030060", "80×35 mm", 4, "rect", 80, 35)
        ],
        ["theroll"] =
        [
            new("RL010010", "40×30 mm", 20, "circle", 40, 30),
            new("RL010020", "50×40 mm", 16, "circle", 50, 40),
            new("RL010030", "60×50 mm", 12, "circle", 60, 50),
            new("RL010040", "70×60 mm", 8, "circle", 70, 60)
        ],
        ["a3"] =
        [
            new("A301001", "100×70 mm", 4, "rect", 100, 70)
        ]
    };

    public static readonly Dictionary<string, ImportSpec[]> TagBlank = new()
    {
        ["a4"] =
        [
            new("TLF0021", "210×143.5 mm", 2, "tag", 210, 143.5f),
            new("TLF0061", "210×143.5 mm", 4, "tag", 210, 143.5f),
            new("TLF0101", "210×143.5 mm", 6, "tag", 210, 143.5f),
            new("TLH0021", "210×143.5 mm", 2, "tag", 210, 143.5f),
            new("TLH0061", "210×143.5 mm", 4, "tag", 210, 143.5f),
            new("TLH0101", "210×143.5 mm", 6, "tag", 210, 143.5f)
        ],
        ["jet"] =
        [
            new("TJ010010", "50×30 mm", 8, "tag", 50, 30),
            new("TJ010020", "60×40 mm", 6, "tag", 60, 40),
            new("TJ010030", "70×50 mm", 4, "tag", 70, 50)
        ],
        ["theroll"] =
        [
            new("TR010010", "45×35 mm", 10, "tag", 45, 35),
            new("TR010020", "55×45 mm", 8, "tag", 55, 45),
            new("TR010030", "65×55 mm", 6, "tag", 65, 55)
        ]
    };

    public static readonly Dictionary<string, ImportDesign[]> LabelDesign = new()
    {
        ["a4"] =
        [
            new("여름 휴가중", "#여름 #휴가 #바다", "#dbeafe"),
            new("OPEN 준비중", "#오픈 #카페 #안내", "#ffedd5"),
            new("수리완료 검수확인", "#품질 #관리 #제품", "#fef3c7"),
            new("Green Choice", "#친환경 #구매 #감사", "#dcfce7"),
            new("한라봉청 선물세트", "#선물 #식품 #감사", "#fce7f3"),
            new("INCHEON KOREA", "#여행 #스탬프", "#e0e7ff")
        ],
        ["jet"] =
        [
            new("배송주의 라벨", "#배송 #주의", "#fee2e2"),
            new("가격표 기본", "#가격 #매장", "#fef9c3")
        ],
        ["theroll"] =
        [
            new("롤 원형 감사", "#감사 #롤", "#ffe4e6")
        ],
        ["a3"] =
        [
            new("대형 안내 라벨", "#안내 #A3", "#cffafe")
        ]
    };

    public static readonly Dictionary<string, ImportDesign[]> TagDesign = new()
    {
        ["a4"] =
        [
            new("행택 감사", "#감사 #행택", "#fce7f3"),
            new("폴드택 안내", "#안내 #폴드", "#dbeafe")
        ],
        ["jet"] =
        [
            new("제트 태그 샘플", "#제트 #태그", "#ffedd5")
        ],
        ["theroll"] =
        [
            new("롤 태그 샘플", "#롤 #태그", "#dcfce7")
        ]
    };

    public static readonly ImportTemplateTag[] TemplateTags =
    [
        new("#전체", 657, true),
        new("#감사", 336),
        new("#선물", 206),
        new("#인사", 170),
        new("#증정품", 137),
        new("#추석", 107),
        new("#구매", 98),
        new("#카페", 52)
    ];

    public static readonly ImportDesign[] TemplateItems =
    [
        new("젖은 우산은 우산꽂이에", "#안내 #카페", "#dbeafe"),
        new("한라봉청", "#식품 #선물", "#ffedd5"),
        new("Green Choice", "#친환경 #구매", "#dcfce7"),
        new("지구를 위한 작은 실천", "#감사 #친환경", "#d1fae5"),
        new("증정용라벨", "#증정품 #여름", "#fce7f3"),
        new("INCHEON KOREA", "#여행 #스탬프", "#e0e7ff"),
        new("세일라벨", "#이벤트 #세일", "#fee2e2"),
        new("네임스티커", "#카페 #이름", "#f3e8ff"),
        new("감사라벨", "#감사 #선물", "#ffe4e6"),
        new("안내라벨", "#안내 #카페", "#cffafe"),
        new("미끄럼주의", "#안내 #주의", "#fef9c3"),
        new("답례품 라벨", "#답례 #선물", "#fef3c7")
    ];

    public static readonly ImportMyDesign[] MyDesigns =
    [
        new("올리브오일 라벨", "70×50 mm", "2026.08.28", "#ecfdf5", "최근"),
        new("카페 감사 스티커", "40×40 mm", "2026.08.20", "#fff7ed"),
        new("배송 주의 라벨", "50×30 mm", "2026.08.12", "#fef2f2"),
        new("네임택 시안", "60×20 mm", "2026.08.01", "#eef2ff")
    ];
}
