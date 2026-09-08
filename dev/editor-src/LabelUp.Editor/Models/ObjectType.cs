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
    Arc
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
    Rounded
}
