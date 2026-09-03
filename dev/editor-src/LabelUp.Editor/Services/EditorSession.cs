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
    public List<DesignObject> Clipboard { get; private set; } = [];
    public bool ShapeMenuOpen { get; set; }
    public bool DataManagerMinimized { get; set; }
    public float Zoom { get; set; } = 1f;
    public float PanX { get; set; }
    public float PanY { get; set; }
    public bool Dirty { get; set; }
    public bool ShowGrid { get; set; } = true;
    public bool TopBarPinned { get; set; } = true;
    public bool AutoSaveEnabled { get; set; }
    public string Status { get; set; } = "준비됨";
    public string PropsTab { get; set; } = "props";
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
    public ObjectType? PendingInsert { get; set; }
    public string ClipartTab { get; set; } = "clipart";
    public List<UserAsset> UserAssets { get; } = [];
    public VendorImportResult? PendingVendorImport { get; set; }
    public int WorkspaceId { get; set; }
    public int? CurrentShopProductId { get; set; }
    public bool PendingShopBuyNow { get; set; }
    public string? PendingShopPaperNo { get; set; }
    /// <summary>저장 확인 후 이어서 실행할 작업(새 파일 열기·타사 변환).</summary>
    public Func<Task>? PendingContinueAsync { get; set; }
    public bool ConversionBusy { get; private set; }
    public string ConversionFileName { get; private set; } = "";
    public string ConversionDetail { get; private set; } = "";
    public int ConversionPercent { get; private set; }
    public string ErrorTitle { get; set; } = "오류";
    public string ErrorMessage { get; set; } = "";
    /// <summary>문서가 통째로 바뀔 때마다 증가. 미리보기 캐시 무효화용.</summary>
    public int DocumentEpoch { get; private set; }

    /// <summary>Pixels per mm at zoom 1 (screen preview density).</summary>
    public float PxPerMm { get; set; } = 4.2f;

    public const float RulerPx = 28f;
    /// <summary>룰러와 라벨 사이 간격. CSS 픽셀(장치 독립)이라 DPI가 달라도 시각적 거리가 같습니다.</summary>
    public const float RulerGapPx = 20f;
    public const float MinZoom = 0.25f;
    public const float MaxZoom = 8f;

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

    public void BeginConversion(string fileName)
    {
        ConversionBusy = true;
        ConversionFileName = string.IsNullOrWhiteSpace(fileName) ? "파일" : Path.GetFileName(fileName);
        ConversionDetail = "파일을 준비하는 중…";
        ConversionPercent = 0;
        Status = "변환 중…";
        Notify();
    }

    public void UpdateConversion(string detail, int percent)
    {
        ConversionBusy = true;
        if (!string.IsNullOrWhiteSpace(detail))
            ConversionDetail = detail;
        ConversionPercent = Math.Clamp(percent, 0, 100);
        Status = $"변환 중… {ConversionPercent}%";
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
        ErrorTitle = string.IsNullOrWhiteSpace(title) ? "오류" : title;
        ErrorMessage = string.IsNullOrWhiteSpace(message) ? "알 수 없는 오류가 발생했습니다." : message;
        PendingVendorImport = null;
        Status = $"{ErrorTitle} · {ErrorMessage}";
        EditorLog.Error($"{ErrorTitle}: {ErrorMessage}");
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
        return ex switch
        {
            InvalidDataException or NotSupportedException or InvalidOperationException => ex.Message,
            TimeoutException => string.IsNullOrWhiteSpace(ex.Message) ? "변환이 너무 오래 걸립니다. 파일이 너무 큽니다." : ex.Message,
            OutOfMemoryException => "메모리가 부족합니다. 파일이 너무 큽니다.",
            _ => string.IsNullOrWhiteSpace(ex.Message) ? "내부 오류가 발생했습니다." : ex.Message
        };
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
        Dirty = true;
        Notify();
    }

    public async Task ConfirmIfDirtyAsync(Func<Task> continueAsync)
    {
        ArgumentNullException.ThrowIfNull(continueAsync);
        if (!Dirty)
        {
            await continueAsync();
            return;
        }

        PendingContinueAsync = continueAsync;
        OpenDialog(EditorDialog.UnsavedChanges);
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
        ClearSelection();
        Notify();
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
        Notify();
    }

    public void SelectMany(IEnumerable<string> ids)
    {
        SelectedIds.Clear();
        foreach (var id in ids)
            SelectedIds.Add(id);
        SelectedId = SelectedIds.LastOrDefault();
        Notify();
    }

    public void ToggleSelect(string id)
    {
        if (!SelectedIds.Add(id)) SelectedIds.Remove(id);
        SelectedId = SelectedIds.LastOrDefault();
        Notify();
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

    public void SetZoom(float zoom)
    {
        Zoom = Math.Clamp(zoom, MinZoom, MaxZoom);
        Notify();
    }

    public float ComputeFitZoom(float viewW, float viewH)
    {
        var wMm = Math.Max(1f, Document.WidthMm);
        var hMm = Math.Max(1f, Document.HeightMm);
        var pad = (RulerPx + RulerGapPx + 24f) * 2f;
        var availW = Math.Max(80f, viewW - pad);
        var availH = Math.Max(80f, viewH - pad);
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

    public void ApplyPaper(PaperSpec paper, int? shopProductId = null)
    {
        CurrentShopProductId = shopProductId is > 0 ? shopProductId : null;
        Document.ApplyPaper(paper, keepDesign: true);
        Document.Background = paper.LabelColor;
        PageIndex = 0;
        LabelIndex = 0;
        SelectedId = null;
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
    {
        var cell = CurrentCell;
        var obj = DesignObject.CreateDefault(ObjectType.Text, x ?? Document.WidthMm * 0.12f, y ?? Document.HeightMm * 0.28f);
        obj.Width = Document.WidthMm * 0.76f;
        obj.Text = Document.Data?.Get(GlobalLabelIndex, column) is { Length: > 0 } v ? v : $"[{column}]";
        obj.DataBound = true;
        obj.DataColumn = column;
        obj.ZIndex = cell.Objects.Count == 0 ? 1 : cell.Objects.Max(o => o.ZIndex) + 1;
        cell.Objects.Add(obj);
        Select(obj.Id);
        Dirty = true;
        Status = $"자료 연결: {column}";
        return obj;
    }

    public string ResolveObjectText(DesignObject obj, int? globalIndex = null)
    {
        var idx = globalIndex ?? GlobalLabelIndex;
        var text = obj.Text ?? "";

        if (obj.DataBound && !string.IsNullOrWhiteSpace(obj.DataColumn) && Document.Data is { } data)
        {
            var bound = data.Get(idx, obj.DataColumn);
            if (!string.IsNullOrEmpty(bound)) text = bound;
        }

        if (obj.Type is ObjectType.Barcode or ObjectType.Qr)
        {
            var value = obj.BarcodeValue ?? "";
            if (obj.DataBound && !string.IsNullOrWhiteSpace(obj.DataColumn) && Document.Data is { } data2)
            {
                var bound = data2.Get(idx, obj.DataColumn);
                if (!string.IsNullOrEmpty(bound)) value = bound;
            }
            return value;
        }

        if (obj.TextMode == TextMode.Custom || obj.CustomKind is "date" or "time" or "serial" or "hexserial")
            text = FormtecRecords.ExpandCustom(obj, idx);

        return text;
    }
}
