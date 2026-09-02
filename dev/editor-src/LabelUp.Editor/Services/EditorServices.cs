using System.Text.Json;
using LabelUp.Editor.Models;
using Microsoft.JSInterop;

namespace LabelUp.Editor.Services;

public sealed class HistoryService
{
    private readonly Stack<string> _undo = new();
    private readonly Stack<string> _redo = new();

    public bool CanUndo => _undo.Count > 0;
    public bool CanRedo => _redo.Count > 0;

    public void Push(LabelDocument doc)
    {
        _undo.Push(JsonSerializer.Serialize(doc, LabelDocumentJson.Compact));
        _redo.Clear();
        if (_undo.Count > 80)
        {
            var keep = _undo.Take(60).Reverse().ToList();
            _undo.Clear();
            foreach (var s in keep) _undo.Push(s);
        }
    }

    public LabelDocument? Undo(LabelDocument current)
    {
        if (_undo.Count == 0) return null;
        _redo.Push(JsonSerializer.Serialize(current, LabelDocumentJson.Compact));
        return LabelDocumentJson.Parse(_undo.Pop());
    }

    public LabelDocument? Redo(LabelDocument current)
    {
        if (_redo.Count == 0) return null;
        _undo.Push(JsonSerializer.Serialize(current, LabelDocumentJson.Compact));
        return LabelDocumentJson.Parse(_redo.Pop());
    }

    public void Clear()
    {
        _undo.Clear();
        _redo.Clear();
    }
}

public sealed class DraftStorage(IJSRuntime js)
{
    private const string Key = "labelup.editor.draft.v3";
    private const string LegacyKey = "labelup.editor.draft.v1";
    private const string MapKey = "labelup.editor.vendor-map.v1";

    public async Task SaveAsync(LabelDocument doc)
    {
        var json = LabelDocumentJson.Serialize(doc, indent: false);
        await js.InvokeVoidAsync("labelUpEditor.saveDraft", Key, json);
        EditorLog.Info("초안 저장");
    }

    public async Task<LabelDocument?> LoadAsync()
    {
        var json = await js.InvokeAsync<string?>("labelUpEditor.loadDraft", Key);
        if (string.IsNullOrWhiteSpace(json))
            json = await js.InvokeAsync<string?>("labelUpEditor.loadDraft", LegacyKey);
        if (string.IsNullOrWhiteSpace(json)) return null;
        try
        {
            return LabelDocumentJson.Parse(json);
        }
        catch (Exception ex)
        {
            EditorLog.Warn("초안 파싱 실패: " + ex.Message);
            return null;
        }
    }

    public async Task SaveMapAsync(VendorPaperMap map)
    {
        var json = JsonSerializer.Serialize(map, LabelDocumentJson.Options);
        await js.InvokeVoidAsync("labelUpEditor.saveDraft", MapKey, json);
    }

    public async Task<VendorPaperMap?> LoadMapAsync()
    {
        var json = await js.InvokeAsync<string?>("labelUpEditor.loadDraft", MapKey);
        if (string.IsNullOrWhiteSpace(json)) return null;
        try
        {
            return JsonSerializer.Deserialize<VendorPaperMap>(json, LabelDocumentJson.Options);
        }
        catch
        {
            return null;
        }
    }
}

public sealed class ExportService(IJSRuntime js)
{
    public async Task DownloadPngAsync(byte[] pngBytes, string fileName)
    {
        var b64 = Convert.ToBase64String(pngBytes);
        await js.InvokeVoidAsync("labelUpEditor.downloadBase64", b64, fileName, "image/png");
    }

    public async Task DownloadTextAsync(string text, string fileName, string mime = "application/json")
    {
        await js.InvokeVoidAsync("labelUpEditor.downloadText", text, fileName, mime);
    }

    public async Task<string?> SaveTextAsAsync(string text, string fileName, string mime = "application/json")
    {
        var saved = await js.InvokeAsync<string?>("labelUpEditor.saveTextAs", text, fileName, mime);
        return string.IsNullOrWhiteSpace(saved) ? null : saved;
    }

    public async Task PrintImageAsync(byte[] pngBytes, string title)
    {
        var b64 = Convert.ToBase64String(pngBytes);
        await js.InvokeVoidAsync("labelUpEditor.printImage", "data:image/png;base64," + b64, title);
    }
}
