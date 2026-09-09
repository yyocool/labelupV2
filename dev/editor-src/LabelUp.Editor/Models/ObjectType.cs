namespace LabelUp.Editor.Models;

public enum ObjectType
{
    Text,
    Rect,
    Ellipse,
    Line,
    Shape,
    Image,
    Barcode,
    Qr,
    Table,
    Clipart,
    Icon,
    Gradient
}

public enum EditorTool
{
    Select,
    Text,
    Barcode,
    Qr,
    Image,
    Table,
    Clipart,
    Icon,
    Shape
}

public enum ShapeKind
{
    Rect,
    RoundRect,
    Circle,
    Triangle,
    Ellipse,
    Line,
    Arrow,
    Polygon,
    Arc,
    Trapezoid,
    Parallelogram,
    Star
}

public enum ArrowHeads
{
    End,
    Start,
    Both
}

public enum HandleKind
{
    None,
    Move,
    Nw, Ne, Sw, Se,
    N, S, E, W,
    Rotate
}

public enum EditorDialog
{
    None,
    PaperPicker,
    ThemePicker,
    PaperMaker,
    PaperMap,
    DataManager,
    DataCreate,
    ColumnBind,
    PrintPreview,
    Clipart,
    Icon,
    Barcode,
    Qr,
    Table,
    VendorImport,
    VendorPicker,
    LabiAi,
    LabelShop,
    ProjectPicker,
    UnsavedChanges,
    Error
}

public enum TextMode
{
    /// <summary>일반텍스트. 구간 서식(RichText)을 쓴다. Extended는 옛 저장본 호환용이다.</summary>
    Normal,
    WordArt,
    /// <summary>옛 확장문자열. 로드 시 Normal로 합친다.</summary>
    Extended,
    Custom
}

public enum WordArtStyle
{
    None,
    ArcUp,
    ArcDown,
    Wave,
    Circle,
    Stretch,
    Rounded,
    /// <summary>아이라벨 점점 크게. 왼쪽에서 오른쪽으로 글자 키가 커진다.</summary>
    GrowRight,
    /// <summary>아이라벨 점점 작게.</summary>
    ShrinkRight,
    /// <summary>아이라벨 위로 크게. 위가 넓은 사다리꼴.</summary>
    TopWide,
    /// <summary>아이라벨 위로 작게. 위가 좁은 사다리꼴.</summary>
    TopNarrow,
    /// <summary>아이라벨 스마일. 아래로 둥근 호.</summary>
    Smile
}
