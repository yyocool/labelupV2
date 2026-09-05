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
    Polygon
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
    Normal,
    WordArt,
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
