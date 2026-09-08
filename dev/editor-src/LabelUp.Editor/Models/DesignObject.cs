using System.Text.Json.Serialization;

namespace LabelUp.Editor.Models;

/// <summary>
/// 통합 디자인 객체. 폼텍·애니라벨·아이라벨에서 확인된 공통 속성
/// (좌표, z-index, 잠금, 자료연결, 텍스트/바코드/이미지/표/클립아트)을 한 JSON으로 저장한다.
/// 일반텍스트·워드아트·사용자정의문자열은 Type=Text + TextMode 로 통합한다.
/// </summary>
public sealed class DesignObject
{
    public string Id { get; set; } = Guid.NewGuid().ToString("N");
    public ObjectType Type { get; set; } = ObjectType.Rect;
    public int ZIndex { get; set; }
    public bool Locked { get; set; }
    public bool Visible { get; set; } = true;

    public float X { get; set; }
    public float Y { get; set; }
    public float Width { get; set; } = 30f;
    public float Height { get; set; } = 15f;
    public float Rotation { get; set; }

    public string Fill { get; set; } = "#7B2840";
    public string Stroke { get; set; } = "#2E2A27";
    public float StrokeWidth { get; set; } = 0.4f;
    public float Opacity { get; set; } = 1f;

    public bool DataBound { get; set; }
    public string? DataColumn { get; set; }
    /// <summary>text / date / image / barcode. 데이터 열을 라벨에 넣을 때 지정.</summary>
    public string DataDisplayKind { get; set; } = DataDisplayFormats.Text;
    public string? DataDateFormat { get; set; }

    public string Text { get; set; } = "LABEL UP";
    public float FontSize { get; set; } = 4.5f;
    public string FontFamily { get; set; } = "Pretendard";
    public bool Bold { get; set; }
    public bool Italic { get; set; }
    public bool Underline { get; set; }
    public bool Strikeout { get; set; }
    public bool Outline { get; set; }
    public bool Shadow { get; set; }
    public bool FlipHorizontal { get; set; }
    public bool FlipVertical { get; set; }
    public string TextAlign { get; set; } = "center";
    public string VerticalAlign { get; set; } = "middle";
    public float LineHeight { get; set; } = 1.2f;
    public float LetterSpacing { get; set; }
    public string TextDirection { get; set; } = "horizontal";
    /// <summary>박스 폭을 넘기면 줄바꿈. char=글자 단위, word=단어 단위, none=없음.</summary>
    public string TextWrap { get; set; } = "char";
    /// <summary>변환 시 넣는 가로 안쪽 여백(mm). 박스 크기는 그대로. 폼텍=2. 우리 박스는 0.</summary>
    public float TextPaddingXMm { get; set; }
    public string? BackgroundFill { get; set; }
    public bool BackgroundTransparent { get; set; } = true;
    public TextMode TextMode { get; set; } = TextMode.Normal;
    /// <summary>일반텍스트 구간 서식. 박스 전체가 아니라 글자 구간의 글꼴·기울기·색이다.</summary>
    public List<TextParagraph>? RichText { get; set; }
    public WordArtStyle WordArtStyle { get; set; } = WordArtStyle.None;
    /// <summary>폼텍 워드 각도(도). 일반형은 상자 기울기. 기본 30.</summary>
    public float WordArtBend { get; set; } = 30f;
    /// <summary>원형 모양 회전(도). S+13.</summary>
    public float WordArtCircleRotation { get; set; }
    /// <summary>원형 글자 방향. false=시계, true=반시계. S+17.</summary>
    public bool WordArtCounterClockwise { get; set; }
    public bool WordArtGuide { get; set; }
    /// <summary>그림자 색. 폼텍 S+20 RGB.</summary>
    public string? ShadowFill { get; set; }
    /// <summary>그림자 각도(도). 0=오른쪽, 90=위. 폼텍 S+24.</summary>
    public float ShadowAngle { get; set; } = 315f;
    /// <summary>그림자 간격(mm). 폼텍 S+28 Extended80. 0이면 기본 오프셋.</summary>
    public float ShadowDistanceMm { get; set; }
    public string CustomKind { get; set; } = "none";
    public string CustomFormat { get; set; } = "yyyy-MM-dd";
    public int SerialStart { get; set; } = 1;
    public int SerialStep { get; set; } = 1;
    public int SerialDigits { get; set; } = 4;
    /// <summary>생성할 라벨 수. 시작값부터 증가값씩 올린다. DGF에는 없다.</summary>
    public int SerialRepeat { get; set; } = 1;

    public string BarcodeFormat { get; set; } = "CODE_128";
    public string BarcodeValue { get; set; } = "12345678";
    public bool BarcodeShowText { get; set; } = true;
    public bool BarcodeShowStartEnd { get; set; }
    /// <summary>
    /// 바코드 변환 출처. formtec만 폼텍 인코딩(PZN 하이픈+체크 등)을 쓴다.
    /// 다른 회사 변환은 이후 회사별 기본값으로 따로 둔다.
    /// </summary>
    public string? BarcodeVendor { get; set; }

    [JsonIgnore]
    public bool UsesFormtecBarcodeRules
        => string.Equals(BarcodeVendor, "formtec", StringComparison.OrdinalIgnoreCase)
           || string.IsNullOrWhiteSpace(BarcodeVendor);

    public string QrEcc { get; set; } = "M";
    public string QrKind { get; set; } = "text";

    public string? ImageData { get; set; }
    public string ImageFit { get; set; } = "contain";
    public string? Svg { get; set; }
    public List<SvgPart>? SvgParts { get; set; }
    public string? IconName { get; set; }
    public string? ClipartId { get; set; }

    public int TableRows { get; set; } = 2;
    public int TableCols { get; set; } = 2;
    public List<string> TableCells { get; set; } = [];
    public List<string?> TableRowFills { get; set; } = [];
    public List<string?> TableColFills { get; set; } = [];
    public List<string?> TableCellFills { get; set; } = [];
    public float TableBorderWidth { get; set; } = 0.2f;

    public string? TableCellFill(int row, int col)
    {
        var cell = GetTableCellFill(row, col);
        if (!string.IsNullOrWhiteSpace(cell))
            return cell;
        if (row >= 0 && row < TableRowFills.Count && !string.IsNullOrWhiteSpace(TableRowFills[row]))
            return TableRowFills[row];
        if (col >= 0 && col < TableColFills.Count && !string.IsNullOrWhiteSpace(TableColFills[col]))
            return TableColFills[col];
        if (!BackgroundTransparent && !string.IsNullOrWhiteSpace(BackgroundFill)
            && BackgroundFill is not "transparent" and not "none")
            return BackgroundFill;
        return null;
    }

    public ShapeKind ShapeKind { get; set; } = ShapeKind.Rect;
    public ArrowHeads ArrowHeads { get; set; } = ArrowHeads.End;
    public int PolygonSides { get; set; } = 5;
    public float CornerRadiusMm { get; set; } = 2.4f;
    /// <summary>선 종류. 0=실선, 1=파선, 2=점선, 3=1점쇄선, 4=2점쇄선.</summary>
    public int DashStyle { get; set; }
    /// <summary>그라데이션 끝 색. 시작 색은 Fill.</summary>
    public string GradientEnd { get; set; } = "#FFFFFF";
    /// <summary>0=좌→우, 1=우→좌, 2=위→아래, 3=아래→위.</summary>
    public int GradientDirection { get; set; }
    public int GradientPrecision { get; set; } = 100;

    public static bool IsShape(ObjectType type)
        => type is ObjectType.Rect or ObjectType.Ellipse or ObjectType.Line or ObjectType.Shape;

    [JsonIgnore]
    public string DisplayText => Text;

    public DesignObject Clone()
    {
        return new DesignObject
        {
            Id = Id,
            Type = Type,
            ZIndex = ZIndex,
            Locked = Locked,
            Visible = Visible,
            X = X,
            Y = Y,
            Width = Width,
            Height = Height,
            Rotation = Rotation,
            Fill = Fill,
            Stroke = Stroke,
            StrokeWidth = StrokeWidth,
            Opacity = Opacity,
            DataBound = DataBound,
            DataColumn = DataColumn,
            DataDisplayKind = DataDisplayKind,
            DataDateFormat = DataDateFormat,
            Text = Text,
            FontSize = FontSize,
            FontFamily = FontFamily,
            Bold = Bold,
            Italic = Italic,
            Underline = Underline,
            Strikeout = Strikeout,
            Outline = Outline,
            Shadow = Shadow,
            FlipHorizontal = FlipHorizontal,
            FlipVertical = FlipVertical,
            TextAlign = TextAlign,
            VerticalAlign = VerticalAlign,
            LineHeight = LineHeight,
            LetterSpacing = LetterSpacing,
            TextDirection = TextDirection,
            TextWrap = TextWrap,
            TextPaddingXMm = TextPaddingXMm,
            BackgroundFill = BackgroundFill,
            BackgroundTransparent = BackgroundTransparent,
            TextMode = TextMode,
            RichText = RichText?.Select(p => p.Clone()).ToList(),
            WordArtStyle = WordArtStyle,
            WordArtBend = WordArtBend,
            WordArtCircleRotation = WordArtCircleRotation,
            WordArtCounterClockwise = WordArtCounterClockwise,
            WordArtGuide = WordArtGuide,
            ShadowFill = ShadowFill,
            ShadowAngle = ShadowAngle,
            ShadowDistanceMm = ShadowDistanceMm,
            CustomKind = CustomKind,
            CustomFormat = CustomFormat,
            SerialStart = SerialStart,
            SerialStep = SerialStep,
            SerialDigits = SerialDigits,
            SerialRepeat = SerialRepeat,
            BarcodeFormat = BarcodeFormat,
            BarcodeValue = BarcodeValue,
            BarcodeShowText = BarcodeShowText,
            BarcodeShowStartEnd = BarcodeShowStartEnd,
            BarcodeVendor = BarcodeVendor,
            QrEcc = QrEcc,
            QrKind = QrKind,
            ImageData = ImageData,
            ImageFit = ImageFit,
            Svg = Svg,
            SvgParts = SvgParts?.Select(p => p.Clone()).ToList(),
            IconName = IconName,
            ClipartId = ClipartId,
            TableRows = TableRows,
            TableCols = TableCols,
            TableCells = [.. TableCells],
            TableRowFills = [.. TableRowFills],
            TableColFills = [.. TableColFills],
            TableCellFills = [.. TableCellFills],
            TableBorderWidth = TableBorderWidth,
            ShapeKind = ShapeKind,
            ArrowHeads = ArrowHeads,
            PolygonSides = PolygonSides,
            CornerRadiusMm = CornerRadiusMm,
            DashStyle = DashStyle,
            GradientEnd = GradientEnd,
            GradientDirection = GradientDirection,
            GradientPrecision = GradientPrecision
        };
    }

    public static DesignObject CreateDefault(ObjectType type, float x, float y)
    {
        var o = new DesignObject { Type = type, X = x, Y = y };
        switch (type)
        {
            case ObjectType.Text:
                o.Width = 36f;
                o.Height = 10f;
                o.Fill = "#2E2A27";
                o.StrokeWidth = 0f;
                o.BackgroundTransparent = true;
                o.BackgroundFill = "transparent";
                o.Text = "새 텍스트";
                o.FontSize = 5f;
                o.TextMode = TextMode.Normal;
                break;
            case ObjectType.Rect:
            case ObjectType.Shape:
                ApplyShapeDefaults(o, ShapeKind.Rect);
                break;
            case ObjectType.Ellipse:
                ApplyShapeDefaults(o, ShapeKind.Ellipse);
                break;
            case ObjectType.Line:
                ApplyShapeDefaults(o, ShapeKind.Line);
                break;
            case ObjectType.Image:
                o.Width = 28f;
                o.Height = 28f;
                o.Fill = "transparent";
                o.StrokeWidth = 0f;
                o.BackgroundTransparent = true;
                break;
            case ObjectType.Barcode:
                o.Width = 40f;
                o.Height = 16f;
                o.Fill = "#2E2A27";
                o.StrokeWidth = 0f;
                o.BackgroundTransparent = true;
                o.BarcodeFormat = "CODE_128";
                o.BarcodeValue = "LABELUP";
                o.FontSize = 2.4f;
                break;
            case ObjectType.Qr:
                o.Width = 22f;
                o.Height = 22f;
                o.Fill = "#2E2A27";
                o.StrokeWidth = 0f;
                o.BackgroundTransparent = true;
                o.BarcodeFormat = "QR_CODE";
                o.BarcodeValue = "https://labelup.kr";
                o.BarcodeShowText = false;
                break;
            case ObjectType.Table:
                o.Width = 40f;
                o.Height = 18f;
                o.Fill = "#2E2A27";
                o.Stroke = "#2E2A27";
                o.StrokeWidth = 0.2f;
                o.TableRows = 2;
                o.TableCols = 2;
                o.TableCells = ["항목", "값", "A", "1"];
                o.FontSize = 2.8f;
                o.BackgroundFill = "transparent";
                o.BackgroundTransparent = true;
                break;
            case ObjectType.Clipart:
                o.Width = 18f;
                o.Height = 18f;
                o.Fill = "#7B2840";
                o.StrokeWidth = 0f;
                o.BackgroundTransparent = true;
                o.ClipartId = "heart";
                o.Svg = SvgLibrary.HeartPath;
                break;
            case ObjectType.Icon:
                o.Width = 12f;
                o.Height = 12f;
                o.Fill = "#7B2840";
                o.StrokeWidth = 0f;
                o.BackgroundTransparent = true;
                o.IconName = "star";
                o.Svg = SvgLibrary.StarPath;
                break;
            case ObjectType.Gradient:
                o.Width = 28f;
                o.Height = 12f;
                o.Fill = "#000000";
                o.GradientEnd = "#FFFFFF";
                o.GradientDirection = 0;
                o.GradientPrecision = 100;
                o.StrokeWidth = 0f;
                o.BackgroundTransparent = false;
                break;
        }

        return o;
    }

    public static DesignObject CreateShape(ShapeKind kind, float x, float y)
    {
        var o = new DesignObject { Type = ObjectType.Shape, X = x, Y = y };
        ApplyShapeDefaults(o, kind);
        return o;
    }

    private static void ApplyShapeDefaults(DesignObject o, ShapeKind kind)
    {
        o.Type = ObjectType.Shape;
        o.ShapeKind = kind;
        switch (kind)
        {
            case ShapeKind.Circle:
                o.Width = 22f;
                o.Height = 22f;
                o.Fill = "transparent";
                o.Stroke = "#2E2A27";
                o.StrokeWidth = 0.4f;
                break;
            case ShapeKind.Ellipse:
                o.Width = 28f;
                o.Height = 18f;
                o.Fill = "transparent";
                o.Stroke = "#2E2A27";
                o.StrokeWidth = 0.4f;
                break;
            case ShapeKind.Arc:
                o.Width = 28f;
                o.Height = 14f;
                o.Fill = "transparent";
                o.Stroke = "#2E2A27";
                o.StrokeWidth = 0.4f;
                break;
            case ShapeKind.Triangle:
                o.Width = 22f;
                o.Height = 20f;
                o.Fill = "transparent";
                o.Stroke = "#2E2A27";
                o.StrokeWidth = 0.4f;
                break;
            case ShapeKind.RoundRect:
                o.Width = 32f;
                o.Height = 18f;
                o.Fill = "transparent";
                o.Stroke = "#2E2A27";
                o.StrokeWidth = 0.4f;
                o.CornerRadiusMm = 3.2f;
                break;
            case ShapeKind.Line:
                o.Width = 40f;
                o.Height = 1.2f;
                o.Fill = "transparent";
                o.Stroke = "#2E2A27";
                o.StrokeWidth = 0.6f;
                break;
            case ShapeKind.Arrow:
                o.Width = 36f;
                o.Height = 8f;
                o.Fill = "transparent";
                o.Stroke = "#2E2A27";
                o.StrokeWidth = 0.8f;
                o.ArrowHeads = ArrowHeads.End;
                break;
            case ShapeKind.Polygon:
                o.Width = 22f;
                o.Height = 22f;
                o.Fill = "transparent";
                o.Stroke = "#2E2A27";
                o.StrokeWidth = 0.4f;
                o.PolygonSides = 5;
                break;
            default:
                o.Width = 32f;
                o.Height = 18f;
                o.Fill = "transparent";
                o.Stroke = "#2E2A27";
                o.StrokeWidth = 0.4f;
                o.ShapeKind = ShapeKind.Rect;
                break;
        }
    }

    public string GetTableCell(int row, int col)
    {
        if (row < 0 || col < 0 || TableCols <= 0) return "";
        var i = row * TableCols + col;
        if (i < 0 || i >= TableCells.Count) return "";
        return TableCells[i] ?? "";
    }

    public void SetTableCell(int row, int col, string value)
    {
        if (row < 0 || col < 0 || TableCols <= 0) return;
        var i = row * TableCols + col;
        while (TableCells.Count <= i) TableCells.Add("");
        TableCells[i] = value;
    }

    public string? GetTableCellFill(int row, int col)
    {
        if (row < 0 || col < 0 || TableCols <= 0) return null;
        var i = row * TableCols + col;
        if (i < 0 || i >= TableCellFills.Count) return null;
        return TableCellFills[i];
    }

    public void SetTableCellFill(int row, int col, string? value)
    {
        EnsureTableSize();
        if (row < 0 || col < 0 || TableCols <= 0) return;
        var i = row * TableCols + col;
        if (i < 0 || i >= TableCellFills.Count) return;
        TableCellFills[i] = NormalizeTableFill(value);
    }

    public void SetTableRowFill(int row, string? value)
    {
        EnsureTableSize();
        if (row < 0 || row >= TableRowFills.Count) return;
        TableRowFills[row] = NormalizeTableFill(value);
    }

    public void SetTableColFill(int col, string? value)
    {
        EnsureTableSize();
        if (col < 0 || col >= TableColFills.Count) return;
        TableColFills[col] = NormalizeTableFill(value);
    }

    private static string? NormalizeTableFill(string? value)
        => string.IsNullOrWhiteSpace(value) || value is "transparent" or "none" ? null : value;

    public void EnsureTableSize()
    {
        TableRows = Math.Max(1, TableRows);
        TableCols = Math.Max(1, TableCols);
        var n = TableRows * TableCols;
        while (TableCells.Count < n) TableCells.Add("");
        if (TableCells.Count > n) TableCells.RemoveRange(n, TableCells.Count - n);
        while (TableRowFills.Count < TableRows) TableRowFills.Add(null);
        if (TableRowFills.Count > TableRows) TableRowFills.RemoveRange(TableRows, TableRowFills.Count - TableRows);
        while (TableColFills.Count < TableCols) TableColFills.Add(null);
        if (TableColFills.Count > TableCols) TableColFills.RemoveRange(TableCols, TableColFills.Count - TableCols);
        while (TableCellFills.Count < n) TableCellFills.Add(null);
        if (TableCellFills.Count > n) TableCellFills.RemoveRange(n, TableCellFills.Count - n);
    }
}
