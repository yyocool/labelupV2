using LabelUp.Editor.Models;

namespace LabelUp.Editor.Services;

internal static class VendorImportActions
{
    public static async Task AnalyzeAsync(
        EditorSession session,
        ExternalImportService import,
        string fileName,
        byte[] bytes,
        Func<Task>? yieldUi = null)
    {
        session.BeginConversion(fileName);
        try
        {
            if (yieldUi is not null)
                await yieldUi();

            var report = await import.AnalyzeAsync(fileName, bytes, async (detail, percent) =>
            {
                session.UpdateConversion(detail, percent);
                if (yieldUi is not null)
                    await yieldUi();
            });
            session.EndConversion();
            if (!report.Ok || report.Document is null)
            {
                session.ShowConversionError(report.Error);
                return;
            }

            session.PendingVendorImport = report;
            session.OpenDialog(EditorDialog.VendorImport);
        }
        catch (Exception ex)
        {
            EditorLog.Error("타사 포맷 변환 실패", ex);
            session.EndConversion();
            session.ShowConversionError(ex);
        }
    }
}
