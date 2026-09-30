using LabelUp.Editor.Rendering;

namespace LabelUp.Editor.Services;

/// <summary>상단 저장하기와 저장 확인 대화상자가 같은 저장 경로를 쓴다.</summary>
public sealed class DesignSaveService(EditorSession session, DraftStorage drafts, EditorCloudStorage cloud)
{
    public async Task<bool> TrySaveAsync()
    {
        try
        {
            await drafts.SaveAsync(session.Document);

            var loggedIn = await cloud.EnsureLoggedInForSaveAsync();
            if (!loggedIn)
            {
                session.Dirty = false;
                session.Status = "로컬 초안에 저장됨";
                session.Notify();
                return true;
            }
            var layout = await cloud.GetUiLayoutAsync();
            var ui = new
            {
                layout,
                zoom = session.Zoom,
                panX = session.PanX,
                panY = session.PanY,
                showGrid = session.ShowGrid,
                topBarPinned = session.TopBarPinned,
                autoSave = session.AutoSaveEnabled,
                propsTab = session.PropsTab,
                propsMinimized = session.PropsMinimized,
                previewMinimized = session.PreviewMinimized,
                dataPanelVisible = session.DataPanelVisible,
                dataPanelExpanded = session.DataPanelExpanded,
                pageIndex = session.PageIndex,
                labelIndex = session.LabelIndex
            };
            var savedId = await cloud.SaveWorkspaceAsync(
                session.Document, ui, CapturePreview(session), session.WorkspaceId);
            if (savedId > 0)
                session.WorkspaceId = savedId;

            session.Dirty = false;
            session.Status = "저장됨 · 계정에 작업 내역을 보관했습니다";
            session.Notify();
            return true;
        }
        catch (Exception ex)
        {
            EditorLog.Error("저장 실패", ex);
            try
            {
                await drafts.SaveAsync(session.Document);
                session.Dirty = false;
                session.Status = "계정 저장 실패 · 로컬 초안은 저장됨";
                session.Notify();
                return true;
            }
            catch
            {
                session.Status = "저장 실패: " + ex.Message;
                session.Notify();
                return false;
            }
        }
    }

    /// <summary>
    /// 계정에 함께 올릴 작은 미리보기 그림. 내 디자인 목록에서 이 그림으로 작업을 알아본다.
    /// 상단 저장하기 단추와 저장 확인 팝업이 같은 그림을 쓴다.
    /// </summary>
    public static string? CapturePreview(EditorSession session)
    {
        try
        {
            session.Document.EnsureStructure();
            var maxMm = Math.Max(session.Document.WidthMm, session.Document.HeightMm);
            var dpi = maxMm <= 1 ? 72f : Math.Clamp(480f / maxMm * 25.4f, 36f, 120f);
            var bytes = DocumentRenderer.ExportPng(
                session.Document,
                session.CurrentCell,
                dpi,
                o => session.ResolveObjectText(o));
            if (bytes.Length is 0 or > 1_500_000)
                return null;
            return "data:image/png;base64," + Convert.ToBase64String(bytes);
        }
        catch (Exception ex)
        {
            EditorLog.Warn("미리보기 이미지 생성 실패: " + ex.Message);
            return null;
        }
    }
}
