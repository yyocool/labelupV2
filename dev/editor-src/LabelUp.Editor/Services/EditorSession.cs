using LabelUp.Editor.Models;
using LabelUp.Editor.Rendering;

namespace LabelUp.Editor.Services;

/// <summary>Shared editor session state for shell components.</summary>
public sealed class EditorSession
{
    public LabelDocument Document { get; private set; } = LabelDocument.CreateBlank();
    public EditorTool Tool { get; set; } = EditorTool.Select;
    public string? SelectedId { get; set; }
    public HashSet<string> SelectedIds { get; } = new(StringComparer.Ordinal);
    /// <summary>
    /// 속성바 내용 칸으로 커서를 보낼 항목. 캔버스에서 바코드·QR을 더블클릭하면 채워지고,
    /// 속성바가 커서를 옮긴 뒤 비운다.
    /// </summary>
    public string? FocusValueObjectId { get; set; }
    /// <summary>
    /// 데이터 판넬에서 커서를 보낼 칸(행, 열 번호). 자료연결 항목을 더블클릭하면 채워지고,
    /// 판넬이 커서를 옮긴 뒤 비운다.
    /// </summary>
    public (int Row, int Col)? FocusDataCell { get; set; }
    public List<DesignObject> Clipboard { get; private set; } = [];
    public bool ShapeMenuOpen { get; set; }
    public bool DataManagerMinimized { get; set; }
    public float Zoom { get; set; } = 1f;
    public float PanX { get; set; }
    public float PanY { get; set; }
    public bool Dirty { get; set; }
    public bool ShowGrid { get; set; } = true;
    public bool TopBarPinned { get; set; } = true;
    /// <summary>편집기 크롬 UI(버튼·패널·다이얼로그) 글자/컨트롤 배율. 캔버스 디자인 줌과 별개.</summary>
    public float UiScale { get; set; } = 1f;
    public const float MinUiScale = 0.8f;
    public const float MaxUiScale = 1.4f;
    public bool AutoSaveEnabled { get; set; }
    public string Status { get; set; } = "준비됨";
    public string PropsTab { get; set; } = "layers";
    public bool PropsMinimized { get; set; } = false;
    public bool PreviewMinimized { get; set; }
    public int PageIndex { get; set; }
    public int LabelIndex { get; set; }
    public EditorDialog Dialog { get; set; } = EditorDialog.None;
    public bool ShowAdminTools { get; set; } = false;
    public bool DataPanelVisible { get; set; }
    public bool DataPanelExpanded { get; set; }
    public int DataPage { get; set; }
    public int DataPageSize { get; set; } = 8;
    public float CursorMmX { get; set; }
    public float CursorMmY { get; set; }
    public bool CursorOverLabel { get; set; }
    public string? DragColumn { get; set; }
    public string? PendingBindColumn { get; set; }
    public ObjectType? PendingInsert { get; set; }
    public string ClipartTab { get; set; } = "clipart";
    public List<UserAsset> UserAssets { get; } = [];
    public VendorImportResult? PendingVendorImport { get; set; }
    /// <summary>변환 문서에 있으나 아직 이 PC에서 불러오지 못한 윈도우 글꼴.</summary>
    public List<string> PendingLocalFonts { get; set; } = [];
    public int WorkspaceId { get; set; }
    public int? CurrentShopProductId { get; set; }
    public bool PendingShopBuyNow { get; set; }
    public string? PendingShopPaperNo { get; set; }
    /// <summary>저장 확인 후 이어서 실행할 작업(새 파일 열기·타사 변환).</summary>
    public Func<Task>? PendingContinueAsync { get; set; }
    public bool ConversionBusy { get; private set; }
    /// <summary>진행 창 제목. 타사 변환과 자료 가져오기가 같은 창을 쓴다.</summary>
    public string ConversionTitle { get; private set; } = DefaultConversionTitle;
    private const string DefaultConversionTitle = "변환 중";
    public string ConversionFileName { get; private set; } = "";
    public string ConversionDetail { get; private set; } = "";
    public int ConversionPercent { get; private set; }
    /// <summary>알림 팝업 제목. 오류와 그냥 알림이 같은 팝업을 쓴다.</summary>
    public string ErrorTitle { get; set; } = "오류";
    public string ErrorMessage { get; set; } = "";
    /// <summary>오류가 아닌 그냥 알림인지. 팝업 설명 문구가 달라진다.</summary>
    public bool NoticeOnly { get; private set; }
    /// <summary>알림을 닫은 뒤 이어서 열 팝업. 팝업 둘이 겹치지 않게 차례로 띄운다.</summary>
    public EditorDialog NextDialog { get; set; } = EditorDialog.None;
    /// <summary>문서가 통째로 바뀔 때마다 증가. 미리보기 캐시 무효화용.</summary>
    public int DocumentEpoch { get; private set; }

    /// <summary>Pixels per mm at zoom 1 (screen preview density).</summary>
    public float PxPerMm { get; set; } = 4.2f;

    public const float RulerPx = 28f;
    /// <summary>룰러와 라벨 사이 간격. CSS 픽셀(장치 독립)이라 DPI가 달라도 시각적 거리가 같습니다.</summary>
    public const float RulerGapPx = 20f;
    public const float MinZoom = 0.25f;
    public const float MaxZoom = 4f;

    /// <summary>첫 페인트·용지 변경·화면 맞춤 시 라벨을 워크스페이스에 맞춥니다.</summary>
    public bool PendingFit { get; set; } = true;

    /// <summary>좌측 도구·우측 패널·하단 FAB가 가리는 영역을 뺀 편집 여백.</summary>
    public const float VisibleLeft = 132f;
    public const float VisibleRight = 304f;
    public const float VisibleTop = 24f;
    public const float VisibleBottom = 80f;

    public event Action? Changed;

    public LabelCell CurrentCell
    {
        get
        {
            Document.EnsureStructure();
            PageIndex = Math.Clamp(PageIndex, 0, Document.Pages.Count - 1);
            var page = Document.Pages[PageIndex];
            LabelIndex = Math.Clamp(LabelIndex, 0, page.Cells.Count - 1);
            return page.Cells[LabelIndex];
        }
    }

    public IList<DesignObject> Objects => CurrentCell.Objects;

    public DesignObject? Selected =>
        SelectedIds.Count == 0
            ? null
            : CurrentCell.Objects.LastOrDefault(o => SelectedIds.Contains(o.Id));

    public IReadOnlyList<DesignObject> SelectedObjects =>
        CurrentCell.Objects.Where(o => SelectedIds.Contains(o.Id)).ToList();

    public int GlobalLabelIndex => Document.GlobalIndex(PageIndex, LabelIndex);

    public void Notify()
    {
        var handlers = Changed;
        if (handlers is null) return;
        foreach (var d in handlers.GetInvocationList())
        {
            try
            {
                ((Action)d).Invoke();
            }
            catch (Exception ex)
            {
                EditorLog.Error("화면 갱신 실패", ex);
            }
        }
    }

    /// <param name="title">진행 창 제목. 타사 변환이 아니면 "데이터 가져오는 중"처럼 달리 쓴다.</param>
    public void BeginConversion(string fileName, string? title = null)
    {
        ConversionBusy = true;
        ConversionTitle = string.IsNullOrWhiteSpace(title) ? DefaultConversionTitle : title;
        ConversionFileName = string.IsNullOrWhiteSpace(fileName) ? "파일" : Path.GetFileName(fileName);
        ConversionDetail = "파일을 준비하는 중…";
        ConversionPercent = 0;
        Status = ConversionTitle + "…";
        Notify();
    }

    public void UpdateConversion(string detail, int percent)
    {
        ConversionBusy = true;
        if (!string.IsNullOrWhiteSpace(detail))
            ConversionDetail = detail;
        ConversionPercent = Math.Clamp(percent, 0, 100);
        Status = $"{ConversionTitle}… {ConversionPercent}%";
        Notify();
    }

    public void EndConversion()
    {
        if (!ConversionBusy && ConversionPercent == 0 && string.IsNullOrEmpty(ConversionDetail))
            return;
        ConversionBusy = false;
        ConversionDetail = "";
        ConversionPercent = 0;
        Notify();
    }

    public void ShowError(string title, string message)
    {
        ConversionBusy = false;
        ConversionDetail = "";
        ConversionPercent = 0;
        NoticeOnly = false;
        NextDialog = EditorDialog.None;
        ErrorTitle = string.IsNullOrWhiteSpace(title) ? "오류" : title;
        ErrorMessage = string.IsNullOrWhiteSpace(message) ? "알 수 없는 오류가 발생했습니다." : message;
        PendingVendorImport = null;
        Status = $"{ErrorTitle} · {ErrorMessage}";
        EditorLog.Error($"{ErrorTitle}: {ErrorMessage}");
        OpenDialog(EditorDialog.Error);
    }

    /// <summary>오류가 아닌 알림 팝업. 기록도 Info로 남긴다.</summary>
    /// <param name="next">알림을 닫은 뒤 이어서 열 팝업. None이면 그냥 닫는다.</param>
    public void ShowNotice(string title, string message, EditorDialog next = EditorDialog.None)
    {
        if (string.IsNullOrWhiteSpace(message)) return;
        NoticeOnly = true;
        NextDialog = next;
        ErrorTitle = string.IsNullOrWhiteSpace(title) ? "알림" : title;
        ErrorMessage = message;
        Status = message;
        EditorLog.Info($"{ErrorTitle}: {message}");
        OpenDialog(EditorDialog.Error);
    }

    public void ShowConversionError(Exception ex) =>
        ShowError("변환 에러", FriendlyError(ex));

    public void ShowConversionError(string? message) =>
        ShowError("변환 에러", string.IsNullOrWhiteSpace(message) ? "파일을 변환하지 못했습니다." : message);

    public static string FriendlyError(Exception ex)
    {
        while (ex.InnerException is { } inner && string.IsNullOrWhiteSpace(ex.Message))
            ex = inner;
        var raw = ex switch
        {
            InvalidDataException or NotSupportedException or InvalidOperationException => ex.Message,
            TimeoutException => string.IsNullOrWhiteSpace(ex.Message) ? "변환이 너무 오래 걸립니다. 파일이 너무 큽니다." : ex.Message,
            OutOfMemoryException => "메모리가 부족합니다. 파일이 너무 큽니다.",
            _ => string.IsNullOrWhiteSpace(ex.Message) ? "내부 오류가 발생했습니다." : ex.Message
        };
        return StripJsStack(raw);
    }

    public static string StripJsStack(string? message)
    {
        var msg = (message ?? "").Trim();
        if (msg.Length == 0) return "내부 오류가 발생했습니다.";
        var cut = msg.IndexOf(" at Object.", StringComparison.Ordinal);
        if (cut < 0) cut = msg.IndexOf("\n   at ", StringComparison.Ordinal);
        if (cut > 0) msg = msg[..cut].Trim();
        var dup = msg.IndexOf(" Error:", StringComparison.Ordinal);
        if (dup > 0) msg = msg[..dup].Trim();
        if (msg.StartsWith("Error:", StringComparison.OrdinalIgnoreCase))
            msg = msg[6..].Trim();
        return string.IsNullOrWhiteSpace(msg) ? "내부 오류가 발생했습니다." : msg.TrimEnd('.');
    }

    public void OpenDialog(EditorDialog dialog)
    {
        Dialog = dialog;
        Notify();
    }

    public void CloseDialog()
    {
        Dialog = EditorDialog.None;
        Notify();
    }

    public void ReplaceDocument(LabelDocument doc, bool keepSelection = false)
    {
        CurrentShopProductId = null;
        doc.EnsureStructure();
        Document = doc;
        DocumentEpoch++;
        DocumentRenderer.ClearImageCache();
        if (keepSelection)
        {
            PageIndex = Math.Clamp(PageIndex, 0, doc.Pages.Count - 1);
            LabelIndex = Math.Clamp(LabelIndex, 0, doc.Pages[PageIndex].Cells.Count - 1);
            if (SelectedIds.Count == 0 || CurrentCell.Objects.All(o => !SelectedIds.Contains(o.Id)))
                ClearSelection();
        }
        else
        {
            PageIndex = 0;
            LabelIndex = 0;
            ClearSelection();
        }
        ApplyCurrentSlotSize();
        if (doc.Data is { RowCount: > 0 })
            doc.EnsurePagesForData();
        ShowDataPanelIfPresent();
        BoundImageCache.Request(doc.Paper.DesignImageUrl);
        Dirty = true;
        Notify();
    }

    /// <summary>
    /// 새 디자인을 시작한다. 쓰던 용지는 그대로 두고 라벨에 올린 것과 자료표만 비운다.
    /// 계정에 저장해 둔 작업과는 연을 끊어(WorkspaceId 초기화), 다음 저장이 그 작업을
    /// 덮어쓰지 않고 새 항목으로 들어가게 한다.
    /// </summary>
    public void StartNewDesign()
    {
        var paper = Document.Paper.Clone();
        var doc = new LabelDocument
        {
            Paper = paper,
            Background = paper.LabelColor
        };
        doc.Pages.Add(LabelPage.Create(0, paper.LabelsPerPage));

        WorkspaceId = 0;
        ReplaceDocument(doc);
        // 빈 디자인은 아직 고친 것이 없다. 자동저장이 곧바로 빈 항목을 만들지 않게 한다.
        Dirty = false;
        Status = "새 디자인을 시작했습니다";
        EditorLog.Info($"새 디자인 시작 · 용지 {paper.PaperNo}");
        Notify();
    }

    public void ShowDataPanelIfPresent(bool expand = false)
    {
        Document.EnsureDataFromBoundColumns();
        var has = Document.Data is { Columns.Count: > 0 };
        DataPanelVisible = has;
        if (has)
        {
            DataPanelExpanded = expand;
            DataPage = 0;
        }
    }

    /// <summary>쓰고 있는 자료표가 있는지. 열만 있고 행이 없어도 판넬에는 보인다.</summary>
    public bool HasConnectedData => Document.Data is { ColumnCount: > 0 };

    /// <summary>
    /// 데이터 생성하기. 이미 쓰고 있는 자료가 있으면 지워도 되는지 먼저 확인받는다.
    /// 새로 만든 자료는 기존 자료표를 덮어쓴다.
    /// </summary>
    public void RequestDataCreate()
        => OpenDialog(HasConnectedData ? EditorDialog.DataCreateConfirm : EditorDialog.DataCreate);

    /// <summary>
    /// 자료연결 항목의 값이 든 판넬 칸을 펼쳐 커서를 보낸다. 라벨 번호가 곧 자료 행 번호다
    /// (1행 → 1번 라벨). 속성창에서는 값을 못 바꾸므로 고치는 길은 판넬뿐이다.
    /// </summary>
    /// <returns>커서를 보낼 칸을 찾았는지.</returns>
    public bool OpenDataCellFor(DesignObject obj)
    {
        if (!obj.DataBound) return false;
        ShowDataPanelIfPresent(expand: true);

        var column = (obj.DataColumn ?? "").Trim();
        var sheet = Document.Data;
        var col = ColumnIndex(sheet, column);
        if (sheet is null || col < 0)
        {
            Status = column.Length == 0
                ? "연결된 열 이름이 없습니다"
                : $"자료표에 {column} 열이 없습니다";
            Notify();
            return false;
        }

        var row = GlobalLabelIndex;
        if (row < 0 || row >= sheet.RowCount)
        {
            Status = $"{row + 1}번 라벨에 해당하는 자료 행이 없습니다. 판넬에서 행을 추가하세요";
            Notify();
            return false;
        }

        FocusDataCell = (row, col);
        Status = $"자료 {row + 1}행 · {sheet.Columns[col]} 열 — 판넬에서 고칠 수 있습니다";
        Notify();
        return true;
    }

    /// <summary>열 이름으로 열 번호 찾기. 변환 파일은 [열]·{@열}처럼 괄호가 붙어 오기도 한다.</summary>
    private static int ColumnIndex(DataSheet? sheet, string column)
    {
        if (sheet is null || column.Length == 0) return -1;
        var at = sheet.Columns.FindIndex(c => string.Equals(c, column, StringComparison.OrdinalIgnoreCase));
        if (at >= 0) return at;
        var bare = column.Trim('[', ']', '{', '}', '@').Trim();
        return bare.Length == 0
            ? -1
            : sheet.Columns.FindIndex(c => string.Equals(c, bare, StringComparison.OrdinalIgnoreCase));
    }

    /// <summary>임시: 새 파일·변환 시 저장 확인 대화상자를 띄우지 않는다.</summary>
    public async Task ConfirmIfDirtyAsync(Func<Task> continueAsync)
    {
        ArgumentNullException.ThrowIfNull(continueAsync);
        PendingContinueAsync = null;
        if (Dialog == EditorDialog.UnsavedChanges)
            CloseDialog();
        await continueAsync();
    }

    public void CancelPendingContinue()
    {
        PendingContinueAsync = null;
        if (Dialog == EditorDialog.UnsavedChanges)
            CloseDialog();
        else
            Notify();
    }

    public void SelectCell(int pageIndex, int labelIndex)
    {
        Document.EnsureStructure();
        PageIndex = Math.Clamp(pageIndex, 0, Document.Pages.Count - 1);
        LabelIndex = Math.Clamp(labelIndex, 0, Document.Pages[PageIndex].Cells.Count - 1);
        ApplyCurrentSlotSize();
        ClearSelection();
        Notify();
    }

    private void ApplyCurrentSlotSize()
    {
        var slots = Document.Paper.CustomSlots;
        if (slots is not { Count: > 0 }) return;
        var i = Math.Clamp(LabelIndex, 0, slots.Count - 1);
        Document.Paper.LabelWidthMm = slots[i].W;
        Document.Paper.LabelHeightMm = slots[i].H;
    }

    public void ClearSelection()
    {
        SelectedIds.Clear();
        SelectedId = null;
    }

    public void Select(string? id)
    {
        SelectedIds.Clear();
        if (id is not null) SelectedIds.Add(id);
        SelectedId = id;
        PreparePlainText();
        Notify();
    }

    public void SelectMany(IEnumerable<string> ids)
    {
        SelectedIds.Clear();
        foreach (var id in ids)
            SelectedIds.Add(id);
        SelectedId = SelectedIds.LastOrDefault();
        PreparePlainText();
        Notify();
    }

    /// <summary>현재 라벨 칸에서 화면에 보이는 항목을 모두 선택한다. 라벨 밖으로 완전히 벗어난 항목은 뺀다.</summary>
    /// <returns>선택된 항목 수.</returns>
    public int SelectAllInLabel()
    {
        var w = Document.WidthMm;
        var h = Document.HeightMm;
        var ids = CurrentCell.Objects
            .Where(o => o.Visible && !LabelDocument.IsOutsideLabel(o, w, h))
            .Select(o => o.Id)
            .ToList();
        SelectMany(ids);
        return ids.Count;
    }

    public void ToggleSelect(string id)
    {
        if (!SelectedIds.Add(id)) SelectedIds.Remove(id);
        SelectedId = SelectedIds.LastOrDefault();
        PreparePlainText();
        Notify();
    }

    private void PreparePlainText()
    {
        foreach (var o in SelectedObjects)
        {
            if (o.Type != ObjectType.Text || !TextModes.IsPlain(o.TextMode)) continue;
            TextModes.Unify(o);
            RichTextModel.Ensure(o);
        }
    }

    public void SetTool(EditorTool tool)
    {
        Tool = tool;
        ShapeMenuOpen = false;
        Notify();
    }

    public DesignObject PlaceDefault(ObjectType type, Action<DesignObject>? setup = null)
    {
        var obj = DesignObject.CreateDefault(type, Document.WidthMm * 0.18f, Document.HeightMm * 0.22f);
        setup?.Invoke(obj);
        AddObject(obj);
        return obj;
    }

    /// <summary>그림 상자 최소 여백(mm).</summary>
    private const float ImagePadMm = 0.4f;
    private const float ImageMinMm = 2f;

    /// <summary>
    /// 원본 화소 비율을 유지한 채 라벨(캔버스) 안에 들어가도록 맞춤(contain).
    /// maxFillRatio: 여백을 뺀 가용 영역 중 사용할 최대 비율(0~1).
    /// </summary>
    public (float W, float H) FitImageInLabelMm(byte[]? bytes, float maxFillRatio = 0.78f)
    {
        var labelW = Math.Max(ImageMinMm, Document.WidthMm);
        var labelH = Math.Max(ImageMinMm, Document.HeightMm);
        var margin = Math.Max(ImagePadMm, Math.Min(labelW, labelH) * 0.04f);
        var availW = Math.Max(ImageMinMm, labelW - margin * 2);
        var availH = Math.Max(ImageMinMm, labelH - margin * 2);
        maxFillRatio = Math.Clamp(maxFillRatio, 0.15f, 1f);
        availW *= maxFillRatio;
        availH *= maxFillRatio;

        float aspect = 1f;
        if (bytes is { Length: > 0 } && RasterImage.TryMeasure(bytes, out var px, out var py) && px > 0 && py > 0)
            aspect = (float)px / py;

        float cw;
        float ch;
        if (availW / availH > aspect)
        {
            ch = availH;
            cw = ch * aspect;
        }
        else
        {
            cw = availW;
            ch = cw / aspect;
        }

        return (Math.Max(ImageMinMm, cw), Math.Max(ImageMinMm, ch));
    }

    /// <summary>
    /// 새로 넣는 그림 상자 크기(mm). 캔버스에 맞게 비율을 유지한다.
    /// </summary>
    public (float W, float H) ImageBoxMm(byte[]? bytes)
    {
        var fill = CurrentCell.Objects.Count == 0 ? 0.78f : 0.52f;
        return FitImageInLabelMm(bytes, fill);
    }

    /// <summary>
    /// 새 그림을 원본 비에 맞춘 상자로 넣는다. 에디터에서 직접 넣은 그림만 비 잠금을 켠다.
    /// 타사 변환으로 들어온 그림은 원본 파일 기하를 지키려고 이 길을 타지 않는다.
    /// </summary>
    public DesignObject PlaceImage(string dataUrl, byte[]? bytes = null)
    {
        var (w, h) = ImageBoxMm(bytes ?? RasterImage.TryReadDataUrl(dataUrl));
        return PlaceDefault(ObjectType.Image, o =>
        {
            o.ImageData = dataUrl;
            o.Width = w;
            o.Height = h;
            o.LockAspectRatio = true;
        });
    }

    /// <summary>
    /// 이미 놓인 그림을 다른 그림으로 갈아끼울 때 상자를 새 원본 비에 맞춘다.
    /// 비 잠금이 꺼져 있으면 사용자가 잡아 놓은 상자를 건드리지 않는다.
    /// </summary>
    public void FitBoxToImageRatio(DesignObject obj, byte[]? bytes)
    {
        if (!obj.LockAspectRatio) return;
        if (bytes is not { Length: > 0 }) return;
        if (!RasterImage.TryMeasure(bytes, out var px, out var py) || px <= 0 || py <= 0) return;

        var contentW = Math.Max(ImageMinMm, obj.Width - ImagePadMm * 2);
        var contentH = contentW * py / px;
        obj.Height = Math.Max(ImageMinMm, contentH) + ImagePadMm * 2;
    }

    public DesignObject PlaceShape(ShapeKind kind)
    {
        var obj = DesignObject.CreateShape(kind, Document.WidthMm * 0.18f, Document.HeightMm * 0.22f);
        AddObject(obj);
        return obj;
    }

    public void AddObject(DesignObject obj)
    {
        obj.X = Math.Clamp(obj.X, 0, Math.Max(0, Document.WidthMm - obj.Width));
        obj.Y = Math.Clamp(obj.Y, 0, Math.Max(0, Document.HeightMm - obj.Height));
        obj.ZIndex = CurrentCell.Objects.Count == 0 ? 1 : CurrentCell.Objects.Max(o => o.ZIndex) + 1;
        CurrentCell.Objects.Add(obj);
        if (obj.Type == ObjectType.Text && TextModes.IsPlain(obj.TextMode))
        {
            TextModes.Unify(obj);
            RichTextModel.Ensure(obj);
        }
        Select(obj.Id);
        Tool = EditorTool.Select;
        Dirty = true;
        Status = "오브젝트 추가됨";
        Notify();
    }

    public void CopySelection()
    {
        Clipboard = SelectedObjects.Select(o => o.Clone()).ToList();
        Status = Clipboard.Count == 0 ? "복사할 항목이 없습니다" : $"{Clipboard.Count}개 복사";
        Notify();
    }

    public void PasteClipboard()
    {
        if (Clipboard.Count == 0)
        {
            Status = "붙여넣을 항목이 없습니다";
            Notify();
            return;
        }

        var ids = new List<string>();
        foreach (var src in Clipboard)
        {
            var copy = src.Clone();
            copy.Id = Guid.NewGuid().ToString("N");
            copy.X += 2.4f;
            copy.Y += 2.4f;
            copy.ZIndex = CurrentCell.Objects.Count == 0 ? 1 : CurrentCell.Objects.Max(o => o.ZIndex) + 1;
            CurrentCell.Objects.Add(copy);
            ids.Add(copy.Id);
        }
        SelectMany(ids);
        Dirty = true;
        Status = $"{ids.Count}개 붙여넣기";
    }

    public void DeleteSelection()
    {
        if (SelectedIds.Count == 0) return;
        CurrentCell.Objects.RemoveAll(x => SelectedIds.Contains(x.Id));
        ClearSelection();
        Dirty = true;
        Status = "삭제됨";
        Notify();
    }

    /// <summary>고른 것 중에 자료표에 연결된 항목이 있는지.</summary>
    public bool SelectionHasDataBound => SelectedObjects.Any(o => o.DataBound);

    /// <summary>고른 자료연결 항목이 쓰는 열 이름.</summary>
    public IReadOnlyList<string> SelectedBoundColumns =>
        SelectedObjects
            .Where(o => o.DataBound)
            .Select(o => (o.DataColumn ?? "").Trim())
            .Where(c => c.Length > 0)
            .Distinct(StringComparer.OrdinalIgnoreCase)
            .ToList();

    /// <summary>
    /// 라벨 칸을 넘나들며 같은 자료연결 항목을 찾는 열쇠. 자료 행 수에 맞춰 칸을 채울 때
    /// 사본마다 새 Id를 주므로 Id로는 맞출 수 없다. 종류·열·자리로 맞춘다.
    /// </summary>
    private static string BoundKey(DesignObject o)
        => $"{(int)o.Type}|{(o.DataColumn ?? "").Trim().ToLowerInvariant()}|{o.DataDisplayKind}|"
           + $"{o.X:0.##}|{o.Y:0.##}|{o.Width:0.##}|{o.Height:0.##}";

    /// <summary>
    /// 고른 항목을 지운다. 자료연결 항목은 모든 라벨 칸에서 함께 지운다.
    /// 한 칸에서만 지우면 라벨 수를 자료 행에 다시 맞출 때 본뜬 칸을 따라 되살아난다.
    /// </summary>
    /// <returns>지운 자료연결 항목 수(모든 칸 합계).</returns>
    public int DeleteSelectionWithBound()
    {
        if (SelectedIds.Count == 0) return 0;
        var keys = SelectedObjects
            .Where(o => o.DataBound)
            .Select(BoundKey)
            .ToHashSet(StringComparer.Ordinal);

        CurrentCell.Objects.RemoveAll(x => SelectedIds.Contains(x.Id));
        var unbound = 0;
        if (keys.Count > 0)
        {
            foreach (var cell in Document.Pages.SelectMany(p => p.Cells))
                unbound += cell.Objects.RemoveAll(o => o.DataBound && keys.Contains(BoundKey(o)));
        }

        ClearSelection();
        Dirty = true;
        Status = unbound > 0 ? "자료연결을 해제하고 모든 라벨에서 지웠습니다" : "삭제됨";
        Notify();
        return unbound;
    }

    /// <summary>맞춤 이름. 단추 도움말과 상태줄에 같은 말을 쓴다.</summary>
    public static string AlignName(AlignEdge edge) => edge switch
    {
        AlignEdge.Left => "왼쪽 정렬",
        AlignEdge.CenterX => "가로 가운데 정렬",
        AlignEdge.Right => "오른쪽 정렬",
        AlignEdge.Top => "세로 위쪽 정렬",
        AlignEdge.CenterY => "세로 가운데 정렬",
        _ => "세로 아래쪽 정렬"
    };

    /// <summary>움직일 항목이 있는지 미리 본다. 없으면 되돌리기 단계를 만들지 않는다.</summary>
    public bool CanAlignSelection(AlignEdge edge) => PlanAlign(edge).Count > 0;

    /// <summary>선택 항목을 기준선에 맞춘다.</summary>
    /// <returns>실제로 움직인 항목 수.</returns>
    public int AlignSelection(AlignEdge edge)
    {
        var moves = PlanAlign(edge);
        if (moves.Count == 0) return 0;
        foreach (var (obj, x, y) in moves)
        {
            obj.X = x;
            obj.Y = y;
        }
        Dirty = true;
        Status = $"{AlignName(edge)} — {moves.Count}개 이동";
        Notify();
        return moves.Count;
    }

    /// <summary>
    /// 맞춤 뒤 자리를 계산한다. 기준은 선택 전체를 감싸는 네모이며, 잠긴 항목도 기준에는 넣되
    /// 움직이지는 않는다(잠근 항목을 그대로 둔 기준선 노릇을 한다). 회전은 무시하고 상자 기준으로 맞춘다.
    /// </summary>
    private List<(DesignObject Obj, float X, float Y)> PlanAlign(AlignEdge edge)
    {
        var moves = new List<(DesignObject, float, float)>();
        var all = SelectedObjects;
        if (all.Count < 2) return moves;

        var left = all.Min(o => o.X);
        var right = all.Max(o => o.X + o.Width);
        var top = all.Min(o => o.Y);
        var bottom = all.Max(o => o.Y + o.Height);

        foreach (var obj in all)
        {
            if (obj.Locked) continue;
            var x = edge switch
            {
                AlignEdge.Left => left,
                AlignEdge.CenterX => (left + right - obj.Width) / 2f,
                AlignEdge.Right => right - obj.Width,
                _ => obj.X
            };
            var y = edge switch
            {
                AlignEdge.Top => top,
                AlignEdge.CenterY => (top + bottom - obj.Height) / 2f,
                AlignEdge.Bottom => bottom - obj.Height,
                _ => obj.Y
            };
            // 0.01mm 안쪽 차이는 이미 맞은 것으로 본다.
            if (Math.Abs(x - obj.X) < 0.01f && Math.Abs(y - obj.Y) < 0.01f) continue;
            moves.Add((obj, x, y));
        }
        return moves;
    }

    public void BringSelectionToFront()
    {
        if (SelectedIds.Count == 0) return;
        var max = CurrentCell.Objects.Count == 0 ? 0 : CurrentCell.Objects.Max(o => o.ZIndex);
        foreach (var o in SelectedObjects.OrderBy(x => x.ZIndex))
            o.ZIndex = ++max;
        Dirty = true;
        Status = "맨 앞으로 이동";
        Notify();
    }

    public void SendSelectionToBack()
    {
        if (SelectedIds.Count == 0) return;
        var min = CurrentCell.Objects.Count == 0 ? 1 : CurrentCell.Objects.Min(o => o.ZIndex);
        foreach (var o in SelectedObjects.OrderByDescending(x => x.ZIndex))
            o.ZIndex = --min;
        Dirty = true;
        Status = "맨 뒤로 이동";
        Notify();
    }

    public void BringSelectionForward() => NudgeSelectionInStack(1);

    public void SendSelectionBackward() => NudgeSelectionInStack(-1);

    /// <summary>Swap selected object(s) with the next neighbor in visual stack order, then rewrite 1..n z-index.</summary>
    private void NudgeSelectionInStack(int direction)
    {
        var objects = CurrentCell.Objects;
        if (objects.Count < 2 || SelectedIds.Count == 0) return;

        var stack = objects
            .Select((o, i) => (o, i))
            .OrderBy(t => t.o.ZIndex)
            .ThenBy(t => t.i)
            .Select(t => t.o)
            .ToList();
        var selected = new HashSet<string>(SelectedIds, StringComparer.Ordinal);
        var indexes = new List<int>();
        for (var i = 0; i < stack.Count; i++)
        {
            if (selected.Contains(stack[i].Id))
                indexes.Add(i);
        }
        if (indexes.Count == 0) return;

        if (direction > 0)
        {
            var top = indexes[indexes.Count - 1];
            if (top >= stack.Count - 1) return;
            if (indexes.Count == 1)
            {
                var swap = stack[top];
                stack[top] = stack[top + 1];
                stack[top + 1] = swap;
            }
            else
            {
                var neighbor = stack[top + 1];
                stack.RemoveAt(top + 1);
                stack.Insert(indexes[0], neighbor);
            }
        }
        else
        {
            var bottom = indexes[0];
            if (bottom <= 0) return;
            if (indexes.Count == 1)
            {
                var swap = stack[bottom];
                stack[bottom] = stack[bottom - 1];
                stack[bottom - 1] = swap;
            }
            else
            {
                var neighbor = stack[bottom - 1];
                stack.RemoveAt(bottom - 1);
                stack.Insert(indexes[indexes.Count - 1], neighbor);
            }
        }

        for (var i = 0; i < stack.Count; i++)
            stack[i].ZIndex = i + 1;
        objects.Clear();
        objects.AddRange(stack);
        Dirty = true;
        Status = direction > 0 ? "한 단계 앞으로" : "한 단계 뒤로";
        Notify();
    }

    /// <summary>
    /// 속성창 목록 기준으로 레이어를 옮긴다. 0이 맨 앞(가장 위).
    /// <paramref name="toIndex"/>는 항목을 뺀 뒤의 삽입 위치이다.
    /// </summary>
    public bool ReorderLayerVisual(int fromIndex, int toIndex)
    {
        var objects = CurrentCell.Objects;
        if (objects.Count < 2) return false;

        var stack = objects
            .Select((o, i) => (o, i))
            .OrderByDescending(t => t.o.ZIndex)
            .ThenByDescending(t => t.i)
            .Select(t => t.o)
            .ToList();
        if (fromIndex < 0 || fromIndex >= stack.Count) return false;

        var moved = stack[fromIndex];
        stack.RemoveAt(fromIndex);
        toIndex = Math.Clamp(toIndex, 0, stack.Count);
        if (toIndex == fromIndex) return false;
        stack.Insert(toIndex, moved);

        stack.Reverse();
        for (var i = 0; i < stack.Count; i++)
            stack[i].ZIndex = i + 1;
        objects.Clear();
        objects.AddRange(stack);
        Dirty = true;
        Status = "레이어 순서 변경";
        EditorLog.Info($"레이어 순서 {moved.Id} {fromIndex}→{toIndex}");
        Notify();
        return true;
    }

    public void SetZoom(float zoom)
    {
        Zoom = Math.Clamp(zoom, MinZoom, MaxZoom);
        Notify();
    }

    public void SetUiScale(float scale)
    {
        UiScale = Math.Clamp(MathF.Round(scale * 20f) / 20f, MinUiScale, MaxUiScale);
        Notify();
    }

    public float ComputeFitZoom(float viewW, float viewH)
    {
        var wMm = Math.Max(1f, Document.WidthMm);
        var hMm = Math.Max(1f, Document.HeightMm);
        var pad = (RulerPx + RulerGapPx + 24f) * 2f;
        var mobileExtra = viewW < 920f ? 96f : 0f;
        var availW = Math.Max(80f, viewW - pad);
        var availH = Math.Max(80f, viewH - pad - mobileExtra);
        var zoom = Math.Min(availW / (wMm * PxPerMm), availH / (hMm * PxPerMm)) * 0.9f;
        return Math.Clamp(zoom, MinZoom, MaxZoom);
    }

    public void FitToView(float viewW, float viewH)
    {
        Zoom = ComputeFitZoom(viewW, viewH);
        PanX = 0;
        PanY = 0;
        PendingFit = false;
        Notify();
    }

    /// <summary>글꼴만 바뀌어도 미리보기 캐시를 버린다.</summary>
    public void InvalidateRender()
    {
        DocumentEpoch++;
        Notify();
    }

    /// <summary>용지 변경 방식을 묻는 팝업이 들고 있는 용지.</summary>
    public PaperSpec? PendingPaper { get; set; }

    /// <summary>위 용지의 상점 상품 번호. 팝업에서 적용할 때 같이 넘긴다.</summary>
    public int? PendingPaperShopId { get; set; }

    /// <summary>
    /// 페이지당 칸 수가 달라지는데 옮길 내용이 있으면, 항목을 그대로 둘지 비율대로 줄일지 물어야 한다.
    /// </summary>
    public bool NeedsPaperChangeChoice(PaperSpec next)
        => Document.Paper.LabelsPerPage != next.LabelsPerPage
           && Document.Pages.Any(page => page.Cells.Any(cell => cell.Objects.Count > 0));

    private IEnumerable<DesignObject> AllObjects()
        => Document.Pages.SelectMany(page => page.Cells).SelectMany(cell => cell.Objects);

    /// <summary>지금 라벨 밖에 있어 화면에 안 그려지는 항목 수. 용지를 키우면 튀어나올 후보다.</summary>
    public int HiddenObjectCount()
    {
        var cur = Document.Paper;
        return AllObjects().Count(obj =>
            LabelDocument.IsOutsideLabel(obj, cur.LabelWidthMm, cur.LabelHeightMm));
    }

    /// <summary>위치를 그대로 두고 용지를 바꿀 때, 새 라벨 밖으로 나가 안 보이게 되는 항목 수.</summary>
    public int ClippedObjectCount(PaperSpec next)
    {
        var cur = Document.Paper;
        return AllObjects().Count(obj =>
            !LabelDocument.IsOutsideLabel(obj, cur.LabelWidthMm, cur.LabelHeightMm)
            && LabelDocument.IsOutsideLabel(obj, next.LabelWidthMm, next.LabelHeightMm));
    }

    public void ApplyPaper(
        PaperSpec paper,
        int? shopProductId = null,
        bool scaleObjects = false,
        bool dropHiddenObjects = false)
    {
        CurrentShopProductId = shopProductId is > 0 ? shopProductId : null;
        Document.ApplyPaper(paper, keepDesign: true, scaleObjects, dropHiddenObjects);
        Document.Background = paper.LabelColor;
        DocumentEpoch++;
        PageIndex = 0;
        LabelIndex = 0;
        SelectedId = null;
        ApplyCurrentSlotSize();
        Dirty = true;
        PendingFit = true;
        Status = $"용지 {paper.PaperNo} 적용";
        Notify();
    }

    public void OpenShopBuyNow(string? paperNo = null)
    {
        PendingShopBuyNow = true;
        PendingShopPaperNo = string.IsNullOrWhiteSpace(paperNo) ? Document.Paper.PaperNo : paperNo.Trim();
        OpenDialog(EditorDialog.LabelShop);
    }

    public void ClearPendingShopBuyNow()
    {
        PendingShopBuyNow = false;
        PendingShopPaperNo = null;
    }

    public DesignObject PlaceBoundText(string column, float? x = null, float? y = null)
        => PlaceBoundColumn(column, DataDisplayFormats.Text, null, null, x, y);

    public DesignObject PlaceBoundColumn(
        string column,
        string kind,
        string? dateFormat = null,
        string? barcodeFormat = null,
        float? x = null,
        float? y = null)
    {
        var cell = CurrentCell;
        var display = string.IsNullOrWhiteSpace(kind) ? DataDisplayFormats.Text : kind.Trim();
        var type = display switch
        {
            DataDisplayFormats.Image => ObjectType.Image,
            DataDisplayFormats.Barcode => BarcodeCatalog.Find(barcodeFormat)?.Is2d == true
                ? ObjectType.Qr
                : ObjectType.Barcode,
            _ => ObjectType.Text
        };
        var n = cell.Objects.Count(o => o.DataBound);
        var sample = Document.Data?.Get(GlobalLabelIndex, column) ?? "";
        var w = display == DataDisplayFormats.Image || type == ObjectType.Qr
            ? Math.Min(24f, Document.WidthMm * 0.42f)
            : Math.Min(36f, Document.WidthMm * 0.72f);
        var h = type switch
        {
            ObjectType.Image => w,
            ObjectType.Qr => w,
            ObjectType.Barcode => 14f,
            _ => 10f
        };
        var col = n % 2;
        var row = n / 2;
        var px = x ?? (4f + col * (w + 2.4f));
        var py = y ?? (4f + row * (h + 2.4f));
        if (px + w > Document.WidthMm - 1f) px = 4f;
        if (py + h > Document.HeightMm - 1f) py = Math.Max(2f, Document.HeightMm - h - 2f);

        var obj = DesignObject.CreateDefault(type, px, py);
        obj.Width = w;
        obj.Height = h;
        obj.DataBound = true;
        obj.DataColumn = column;
        obj.DataDisplayKind = display;
        obj.ZIndex = cell.Objects.Count == 0 ? 1 : cell.Objects.Max(o => o.ZIndex) + 1;

        switch (display)
        {
            case DataDisplayFormats.Date:
                obj.DataDateFormat = string.IsNullOrWhiteSpace(dateFormat) ? "yyyy-MM-dd" : dateFormat;
                obj.Text = DataDisplayFormats.FormatDate(sample, obj.DataDateFormat);
                if (string.IsNullOrWhiteSpace(obj.Text)) obj.Text = $"[{column}]";
                break;
            case DataDisplayFormats.Image:
                obj.ImageData = sample;
                obj.ImageFit = "contain";
                BoundImageCache.Request(sample);
                break;
            case DataDisplayFormats.Barcode:
                obj.BarcodeFormat = string.IsNullOrWhiteSpace(barcodeFormat)
                    ? (type == ObjectType.Qr ? "QR_CODE" : "CODE_128")
                    : barcodeFormat;
                obj.BarcodeValue = string.IsNullOrWhiteSpace(sample) ? $"[{column}]" : sample;
                obj.BarcodeShowText = type == ObjectType.Barcode
                    && !string.Equals(obj.BarcodeFormat, "KOREAN_POST", StringComparison.OrdinalIgnoreCase);
                break;
            default:
                obj.Text = string.IsNullOrWhiteSpace(sample) ? $"[{column}]" : sample;
                break;
        }

        cell.Objects.Add(obj);
        SyncDataLabels(cell.Objects);
        var placed = CurrentCell.Objects.LastOrDefault(o =>
            o.DataBound
            && string.Equals(o.DataColumn, column, StringComparison.OrdinalIgnoreCase)
            && o.Type == type);
        Select(placed?.Id ?? CurrentCell.Objects.LastOrDefault()?.Id);
        Dirty = true;
        var rows = Document.Data?.RowCount ?? 0;
        Status = display switch
        {
            DataDisplayFormats.Date => $"자료 연결(날짜): {column} · {rows}칸",
            DataDisplayFormats.Image => $"자료 연결(이미지): {column} · {rows}칸",
            DataDisplayFormats.Barcode => $"자료 연결({obj.BarcodeFormat}): {column} · {rows}칸",
            _ => $"자료 연결: {column} · {rows}칸"
        };
        EditorLog.Info($"데이터 열 배치 kind={display} col={column} type={obj.Type} labels={rows}");
        Notify();
        return placed ?? obj;
    }

    /// <summary>데이터 행마다 같은 디자인을 순서대로 넣고, 보고 있는 칸을 범위 안에 맞춘다.</summary>
    public void SyncDataLabels(IReadOnlyList<DesignObject>? prototype = null)
    {
        Document.EnsurePagesForData(prototype);
        Document.EnsureStructure();
        PageIndex = Math.Clamp(PageIndex, 0, Document.Pages.Count - 1);
        LabelIndex = Math.Clamp(LabelIndex, 0, Document.Pages[PageIndex].Cells.Count - 1);
        ApplyCurrentSlotSize();
    }

    public int ExpandCustomSerial()
    {
        Document.EnsureStructure();
        var prototype = CurrentCell.Objects;
        if (prototype.All(o => o.CustomKind is not ("serial" or "hexserial")))
        {
            var found = Document.Pages.SelectMany(p => p.Cells).SelectMany(c => c.Objects)
                .FirstOrDefault(o => o.CustomKind is "serial" or "hexserial");
            if (found is null) return 0;
            prototype = Document.Pages[0].Cells[0].Objects;
        }
        var total = Document.EnsureCustomSerialLabels(prototype);
        if (total > 0)
        {
            PageIndex = Math.Clamp(PageIndex, 0, Document.Pages.Count - 1);
            LabelIndex = Math.Clamp(LabelIndex, 0, Document.Pages[PageIndex].Cells.Count - 1);
            Status = $"일련번호 라벨 {total}개 생성 (반복회수, 증가 {prototype.FirstOrDefault(o => o.CustomKind is "serial" or "hexserial")?.SerialStep ?? 1})";
            Dirty = true;
        }
        return total;
    }

    public string ResolveObjectText(DesignObject obj, int? globalIndex = null, DateTime? clock = null)
    {
        var idx = globalIndex ?? GlobalLabelIndex;
        if (obj.Type == ObjectType.Image
            || (obj.Type is ObjectType.Clipart or ObjectType.Icon && IsRasterMedia(obj.ImageData)))
        {
            if (obj.DataBound && !string.IsNullOrWhiteSpace(obj.DataColumn) && Document.Data is { } imgData)
            {
                if (idx < 0 || idx >= imgData.RowCount)
                    return "";
                var url = imgData.Get(idx, obj.DataColumn);
                if (!string.IsNullOrWhiteSpace(url))
                {
                    BoundImageCache.Request(url);
                    return url;
                }
                return "";
            }
            if (!string.IsNullOrWhiteSpace(obj.ImageData))
                BoundImageCache.Request(obj.ImageData);
            return obj.ImageData ?? "";
        }

        if (obj.DataBound && !string.IsNullOrWhiteSpace(obj.DataColumn) && Document.Data is { } data)
        {
            if (idx < 0 || idx >= data.RowCount)
                return "";
            var bound = data.Get(idx, obj.DataColumn);
            if (string.IsNullOrEmpty(bound))
                bound = data.Get(idx, obj.DataColumn.Trim().Trim('[', ']', '{', '}', '@'));
            if (string.IsNullOrEmpty(bound))
                return "";
            if (obj.Type is ObjectType.Barcode or ObjectType.Qr)
                return bound;
            return string.Equals(obj.DataDisplayKind, DataDisplayFormats.Date, StringComparison.OrdinalIgnoreCase)
                ? DataDisplayFormats.FormatDate(bound, obj.DataDateFormat)
                : bound;
        }

        if (obj.Type is ObjectType.Barcode or ObjectType.Qr)
            return obj.BarcodeValue ?? "";

        var text = obj.Text ?? "";
        if (obj.TextMode == TextMode.Custom || obj.CustomKind is "date" or "time" or "serial" or "hexserial")
            text = FormtecRecords.ExpandCustom(obj, idx, clock);

        return text;
    }

    private static bool IsRasterMedia(string? data)
    {
        if (string.IsNullOrWhiteSpace(data)) return false;
        if (data.StartsWith("data:image/", StringComparison.OrdinalIgnoreCase)) return true;
        if (data.StartsWith("http://", StringComparison.OrdinalIgnoreCase)
            || data.StartsWith("https://", StringComparison.OrdinalIgnoreCase)
            || data.StartsWith("//"))
            return true;
        if (data.StartsWith("/assets/", StringComparison.OrdinalIgnoreCase)
            || data.StartsWith("assets/", StringComparison.OrdinalIgnoreCase))
            return true;
        var q = data.IndexOf('?');
        var path = q >= 0 ? data[..q] : data;
        return path.EndsWith(".png", StringComparison.OrdinalIgnoreCase)
               || path.EndsWith(".jpg", StringComparison.OrdinalIgnoreCase)
               || path.EndsWith(".jpeg", StringComparison.OrdinalIgnoreCase)
               || path.EndsWith(".webp", StringComparison.OrdinalIgnoreCase)
               || path.EndsWith(".gif", StringComparison.OrdinalIgnoreCase);
    }
}
