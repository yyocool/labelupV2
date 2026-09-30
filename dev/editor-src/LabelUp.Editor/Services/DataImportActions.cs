using System.Threading;
using Microsoft.AspNetCore.Components.Forms;

namespace LabelUp.Editor.Services;

/// <summary>
/// 자료 파일(엑셀·CSV·MDB) 가져오기. 상단바와 떠 있는 도구 모음이 같은 길을 쓴다.
/// 큰 파일은 오래 걸리므로 진행 막대를 띄우고, 끝나면 데이터 판넬을 바로 보여 준다.
/// </summary>
internal static class DataImportActions
{
    /// <summary>받아들이는 최대 크기. 브라우저 메모리에 다 올려야 해서 제한을 둔다.</summary>
    private const int MaxBytes = 20 * 1024 * 1024;

    private const int ChunkBytes = 64 * 1024;

    /// <summary>파일 읽기에 쓰는 진행률 구간(0~ReadShare).</summary>
    private const int ReadShare = 50;

    private static int _busy;

    /// <param name="yieldUi">
    /// 화면을 다시 그릴 틈을 주는 함수. WebAssembly는 한 줄기로 돌아서 이걸 부르지 않으면
    /// 진행 막대가 끝까지 멈춘 채 있다가 한 번에 사라진다.
    /// </param>
    public static async Task ImportAsync(
        EditorSession session,
        DataImportService dataImport,
        HistoryService history,
        IBrowserFile? file,
        Func<Task>? yieldUi = null)
    {
        if (file is null) return;
        if (Interlocked.Exchange(ref _busy, 1) == 1)
        {
            EditorLog.Warn("자료 가져오기가 이미 진행 중입니다");
            return;
        }

        session.BeginConversion(file.Name, "데이터 가져오는 중");
        try
        {
            if (yieldUi is not null) await yieldUi();

            var bytes = await ReadAllAsync(session, file, yieldUi);

            session.UpdateConversion("표를 읽는 중…", ReadShare + 10);
            if (yieldUi is not null) await yieldUi();
            var sheet = dataImport.Parse(file.Name, bytes);

            session.UpdateConversion($"{sheet.RowCount}행을 라벨에 맞추는 중…", 85);
            if (yieldUi is not null) await yieldUi();

            history.Push(session.Document);
            session.Document.Data = sheet;
            session.SyncDataLabels();
            session.ShowDataPanelIfPresent(expand: false);
            session.Dirty = true;

            session.EndConversion();
            session.Status = $"자료 {sheet.RowCount}행 · {sheet.ColumnCount}열 가져옴";
            // 데이터 판넬은 다른 컴포넌트라 이 알림이 없으면 화면을 누를 때까지 나타나지 않는다.
            session.Notify();
            EditorLog.Info($"자료 판넬 표시: {sheet.RowCount}행 {sheet.ColumnCount}열");
        }
        catch (Exception ex)
        {
            EditorLog.Error("자료 가져오기 실패", ex);
            session.EndConversion();
            session.Status = "자료 가져오기 실패: " + EditorSession.FriendlyError(ex);
            session.Notify();
        }
        finally
        {
            Interlocked.Exchange(ref _busy, 0);
        }
    }

    /// <summary>파일을 조각내어 읽으며 진행률을 올린다. 크기를 모르면 읽은 양만 보여 준다.</summary>
    private static async Task<byte[]> ReadAllAsync(
        EditorSession session, IBrowserFile file, Func<Task>? yieldUi)
    {
        await using var stream = file.OpenReadStream(MaxBytes);
        using var ms = new MemoryStream();
        var buffer = new byte[ChunkBytes];
        var size = file.Size;
        long done = 0;
        var shown = -1;
        int read;
        while ((read = await stream.ReadAsync(buffer)) > 0)
        {
            ms.Write(buffer, 0, read);
            done += read;
            // 조각마다 화면을 그리면 오히려 느려진다. 퍼센트가 바뀔 때만 알린다.
            var percent = size > 0 ? (int)(done * ReadShare / size) : ReadShare / 2;
            if (percent == shown) continue;
            shown = percent;
            session.UpdateConversion(
                $"파일 읽는 중 {done / 1024:N0}KB", Math.Clamp(percent, 1, ReadShare));
            if (yieldUi is not null) await yieldUi();
        }
        return ms.ToArray();
    }
}
