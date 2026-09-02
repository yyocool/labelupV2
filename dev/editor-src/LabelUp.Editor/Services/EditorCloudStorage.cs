using System.Net.Http.Json;
using System.Text.Json;
using System.Text.Json.Serialization;
using LabelUp.Editor.Models;
using Microsoft.JSInterop;

namespace LabelUp.Editor.Services;

public sealed class EditorCloudStorage(IJSRuntime js)
{
    private static readonly JsonSerializerOptions JsonOpts = new()
    {
        PropertyNameCaseInsensitive = true,
        PropertyNamingPolicy = JsonNamingPolicy.CamelCase
    };

    public async Task<bool> IsLoggedInAsync()
    {
        try
        {
            var raw = await js.InvokeAsync<JsonElement>("labelUpEditor.getAuthUser");
            return raw.ValueKind == JsonValueKind.Object;
        }
        catch
        {
            return false;
        }
    }

    public async Task<bool> EnsureLoggedInForSaveAsync()
    {
        if (await IsLoggedInAsync()) return true;
        return await js.InvokeAsync<bool>("labelUpEditor.showSaveAuthPrompt");
    }

    public async Task<int> SaveWorkspaceAsync(LabelDocument document, object? ui, string? previewDataUrl = null, int? workspaceId = null)
    {
        var payload = new
        {
            id = workspaceId is > 0 ? workspaceId : null,
            title = document.Name ?? "새 라벨 디자인",
            document,
            ui,
            preview = previewDataUrl
        };
        var raw = await js.InvokeAsync<JsonElement>("labelUpEditor.saveWorkspace", payload);
        var id = 0;
        if (raw.ValueKind == JsonValueKind.Object && raw.TryGetProperty("id", out var idEl) && idEl.TryGetInt32(out var saved))
            id = saved;
        if (id <= 0)
            id = workspaceId ?? 0;
        if (id > 0)
        {
            try { await js.InvokeVoidAsync("labelUpEditor.setProjectId", id); } catch { }
        }
        return id;
    }

    public async Task<IReadOnlyList<EditorWorkspaceListItem>> ListWorkspacesAsync(int limit = 24)
    {
        try
        {
            var raw = await js.InvokeAsync<JsonElement?>("labelUpEditor.listWorkspaces", limit);
            if (raw is not { ValueKind: JsonValueKind.Object }) return [];
            if (!raw.Value.TryGetProperty("items", out var items) || items.ValueKind != JsonValueKind.Array)
                return [];
            var list = JsonSerializer.Deserialize<List<EditorWorkspaceListItem>>(items.GetRawText(), JsonOpts);
            return list ?? [];
        }
        catch (Exception ex)
        {
            EditorLog.Warn("프로젝트 목록 실패: " + ex.Message);
            return [];
        }
    }

    public async Task ApplyLoadedWorkspaceAsync(EditorSession session, EditorWorkspacePayload workspace)
    {
        if (workspace.Document is null) return;
        session.ReplaceDocument(workspace.Document);
        session.WorkspaceId = workspace.Id;
        session.Dirty = false;
        session.DataPanelVisible = workspace.Document.Data is { RowCount: > 0 };
        if (workspace.Ui is { ValueKind: JsonValueKind.Object } ui)
        {
            ApplySessionUi(session, ui);
            if (ui.TryGetProperty("layout", out var layout) && layout.ValueKind == JsonValueKind.Object)
                await ApplyUiLayoutAsync(layout);
            else
                await ApplyUiLayoutAsync(ui);
        }
        session.Status = string.IsNullOrWhiteSpace(workspace.Title)
            ? "저장된 작업을 불러왔습니다"
            : $"「{workspace.Title}」을(를) 불러왔습니다";
        if (session.WorkspaceId > 0)
        {
            try { await js.InvokeVoidAsync("labelUpEditor.setProjectId", session.WorkspaceId); } catch { }
        }
        session.Notify();
    }

    public static void ApplySessionUi(EditorSession session, JsonElement ui)
    {
        if (ui.TryGetProperty("zoom", out var zoom) && zoom.TryGetSingle(out var z))
            session.Zoom = Math.Clamp(z, 0.25f, 4f);
        if (ui.TryGetProperty("panX", out var panX) && panX.TryGetSingle(out var px))
            session.PanX = px;
        if (ui.TryGetProperty("panY", out var panY) && panY.TryGetSingle(out var py))
            session.PanY = py;
        if (ui.TryGetProperty("showGrid", out var grid) && (grid.ValueKind is JsonValueKind.True or JsonValueKind.False))
            session.ShowGrid = grid.GetBoolean();
        if (ui.TryGetProperty("topBarPinned", out var tbp) && (tbp.ValueKind is JsonValueKind.True or JsonValueKind.False))
            session.TopBarPinned = tbp.GetBoolean();
        if (ui.TryGetProperty("propsTab", out var tab) && tab.ValueKind == JsonValueKind.String)
            session.PropsTab = tab.GetString() ?? session.PropsTab;
        if (ui.TryGetProperty("propsMinimized", out var pm) && (pm.ValueKind is JsonValueKind.True or JsonValueKind.False))
            session.PropsMinimized = pm.GetBoolean();
        if (ui.TryGetProperty("previewMinimized", out var pvm) && (pvm.ValueKind is JsonValueKind.True or JsonValueKind.False))
            session.PreviewMinimized = pvm.GetBoolean();
        if (ui.TryGetProperty("dataPanelVisible", out var dv) && (dv.ValueKind is JsonValueKind.True or JsonValueKind.False))
            session.DataPanelVisible = dv.GetBoolean();
        if (ui.TryGetProperty("dataPanelExpanded", out var de) && (de.ValueKind is JsonValueKind.True or JsonValueKind.False))
            session.DataPanelExpanded = de.GetBoolean();
        if (ui.TryGetProperty("pageIndex", out var pi) && pi.TryGetInt32(out var page))
            session.PageIndex = Math.Max(0, page);
        if (ui.TryGetProperty("labelIndex", out var li) && li.TryGetInt32(out var label))
            session.LabelIndex = Math.Max(0, label);
    }

    public async Task<EditorWorkspacePayload?> LoadWorkspaceAsync(int? workspaceId = null)
    {
        var raw = workspaceId is > 0
            ? await js.InvokeAsync<JsonElement?>("labelUpEditor.loadWorkspace", workspaceId.Value)
            : await js.InvokeAsync<JsonElement?>("labelUpEditor.loadWorkspace");
        if (raw is not { ValueKind: JsonValueKind.Object }) return null;
        try
        {
            return JsonSerializer.Deserialize<EditorWorkspacePayload>(raw.Value.GetRawText(), JsonOpts);
        }
        catch (Exception ex)
        {
            EditorLog.Warn("클라우드 작업공간 파싱 실패: " + ex.Message);
            return null;
        }
    }

    public async Task<JsonElement?> GetUiLayoutAsync()
    {
        try
        {
            return await js.InvokeAsync<JsonElement>("labelUpEditor.getUiLayout");
        }
        catch
        {
            return null;
        }
    }

    public async Task ApplyUiLayoutAsync(object? ui)
    {
        if (ui is null) return;
        try
        {
            await js.InvokeVoidAsync("labelUpEditor.applyUiLayout", ui);
        }
        catch (Exception ex)
        {
            EditorLog.Warn("UI 레이아웃 복원 실패: " + ex.Message);
        }
    }
}

public sealed class EditorWorkspacePayload
{
    public int Id { get; set; }
    public string? Title { get; set; }
    public LabelDocument? Document { get; set; }
    public JsonElement? Ui { get; set; }
    public string? UpdatedAt { get; set; }
}

public sealed class EditorWorkspaceListItem
{
    public int Id { get; set; }
    public string Title { get; set; } = "";
    [JsonPropertyName("preview_url")]
    public string PreviewUrl { get; set; } = "";
    [JsonPropertyName("updated_label")]
    public string UpdatedLabel { get; set; } = "";
    [JsonPropertyName("updated_at")]
    public string? UpdatedAt { get; set; }
}
