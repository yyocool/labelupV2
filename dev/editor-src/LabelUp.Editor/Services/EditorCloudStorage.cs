using System.Net.Http.Json;
using System.Text.Json;
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
        => await js.InvokeAsync<JsonElement?>("labelUpEditor.getAuthUser") is { ValueKind: JsonValueKind.Object };

    public async Task<bool> EnsureLoggedInForSaveAsync()
    {
        if (await IsLoggedInAsync()) return true;
        return await js.InvokeAsync<bool>("labelUpEditor.showSaveAuthPrompt");
    }

    public async Task SaveWorkspaceAsync(LabelDocument document, object? ui)
    {
        var payload = new
        {
            title = document.Name ?? "새 라벨 디자인",
            document,
            ui
        };
        await js.InvokeVoidAsync("labelUpEditor.saveWorkspace", payload);
    }

    public async Task<EditorWorkspacePayload?> LoadWorkspaceAsync()
    {
        var raw = await js.InvokeAsync<JsonElement?>("labelUpEditor.loadWorkspace");
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
    public string? Title { get; set; }
    public LabelDocument? Document { get; set; }
    public JsonElement? Ui { get; set; }
    public string? UpdatedAt { get; set; }
}
