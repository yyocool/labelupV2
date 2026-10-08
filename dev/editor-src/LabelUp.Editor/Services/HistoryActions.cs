using LabelUp.Editor.Models;

namespace LabelUp.Editor.Services;

/// <summary>
/// 실행 취소·다시 실행의 한 갈래 길. 상단바 단추와 Ctrl+Z·Ctrl+Y 가 모두 이 길을 쓴다.
/// </summary>
internal static class HistoryActions
{
    /// <summary>한 단계 되돌린다. 되돌릴 것이 없으면 아무 일도 하지 않는다.</summary>
    public static void Undo(EditorSession session, HistoryService history)
    {
        if (history.Undo(session.Document) is not { } doc) return;
        Apply(session, doc, "실행 취소");
    }

    /// <summary>되돌린 것을 한 단계 복구한다. 복구할 것이 없으면 아무 일도 하지 않는다.</summary>
    public static void Redo(EditorSession session, HistoryService history)
    {
        if (history.Redo(session.Document) is not { } doc) return;
        Apply(session, doc, "다시 실행");
    }

    // 문구를 먼저 정해야 ReplaceDocument 가 끝내면서 보내는 알림에 함께 실린다.
    // 단축키로 들어오면 그 알림 말고는 화면을 다시 그릴 계기가 없다.
    private static void Apply(EditorSession session, LabelDocument doc, string status)
    {
        session.Status = status;
        session.ReplaceDocument(doc, keepSelection: true);
    }
}
