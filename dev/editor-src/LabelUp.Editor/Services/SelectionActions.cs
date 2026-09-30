using LabelUp.Editor.Models;

namespace LabelUp.Editor.Services;

/// <summary>
/// 고른 항목을 지우는 한 갈래 길. 속성바 단추·마우스 오른쪽 메뉴·Delete 키가 모두 이 길을 쓴다.
/// </summary>
internal static class SelectionActions
{
    /// <summary>
    /// 고른 항목을 지운다. 자료표에 연결된 항목이 섞여 있으면 바로 지우지 않고 확인 팝업을 띄운다.
    /// 자료연결 항목은 모든 라벨에서 함께 사라지므로 실수로 지우면 되돌리기가 번거롭다.
    /// </summary>
    public static void DeleteSelection(EditorSession session, HistoryService history)
    {
        if (session.SelectedIds.Count == 0) return;
        if (session.SelectionHasDataBound)
        {
            session.OpenDialog(EditorDialog.DeleteBoundConfirm);
            return;
        }
        history.Push(session.Document);
        session.DeleteSelection();
    }
}
