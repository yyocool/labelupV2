using System.Text.Json;
using Microsoft.JSInterop;

namespace LabelUp.Editor.Services;

/// <summary>
/// 프린터 이송 오차를 메우는 인쇄 배율 보정.
///
/// 편집기는 용지 규격 그대로 1:1 로 내보내는 것이 원칙이고 그 원칙은 바뀌지 않는다.
/// 다만 프린터는 세로로 종이를 끌어 보내면서 보통 0.3~0.5% 쯤 어긋난다. 고장이 아니라
/// 정상 범위의 기계 특성이라 고칠 길이 없고, 그만큼 미리 줄여 보내는 수밖에 없다.
/// A827(27행 × 10mm = 270mm)에서 271mm 로 나오면 세로만 0.370% 길다는 뜻이다.
///
/// 값을 문서가 아니라 이 브라우저에 담는 까닭이 있다. 보정값은 디자인의 성질이 아니라
/// 그 프린터의 성질이다. 문서에 넣으면 파일을 남에게 줬을 때 남의 프린터에 내 보정이
/// 따라가 그쪽이 도리어 틀어진다. 틀린 값을 가진 파일이 도는 것은 보정이 없는 것보다 나쁘다.
/// </summary>
public sealed class PrintCalibration(IJSRuntime js)
{
    /// <summary>
    /// 오타 한 번에 인쇄물이 못 쓰게 되는 것을 막는 울타리. 이송 오차는 1%도 큰 축이라
    /// 이 범위를 벗어나는 값은 재어서 나온 값일 수 없다.
    /// </summary>
    private const float MinPct = 95f;
    private const float MaxPct = 105f;

    private bool _loaded;

    /// <summary>가로 배율(%). 100이면 보정하지 않는다.</summary>
    public float ScaleXPct { get; private set; } = 100f;

    /// <summary>세로 배율(%). 100이면 보정하지 않는다.</summary>
    public float ScaleYPct { get; private set; } = 100f;

    public bool HasAdjustment => !IsNeutral(ScaleXPct) || !IsNeutral(ScaleYPct);

    public async Task LoadAsync()
    {
        if (_loaded) return;
        _loaded = true;
        try
        {
            var raw = await js.InvokeAsync<string?>("labelUpEditor.getPrintCalibration");
            if (string.IsNullOrWhiteSpace(raw)) return;
            var saved = JsonSerializer.Deserialize<Stored>(raw);
            if (saved is null) return;
            ScaleXPct = Clamp(saved.X);
            ScaleYPct = Clamp(saved.Y);
            if (HasAdjustment)
                EditorLog.Info($"인쇄 배율 보정: 가로 {ScaleXPct:0.###}% · 세로 {ScaleYPct:0.###}%");
        }
        catch (Exception ex)
        {
            EditorLog.Warn("인쇄 배율 보정 읽기 실패: " + ex.Message);
        }
    }

    public async Task SetAsync(float xPct, float yPct)
    {
        ScaleXPct = Clamp(xPct);
        ScaleYPct = Clamp(yPct);
        try
        {
            var json = JsonSerializer.Serialize(new Stored(ScaleXPct, ScaleYPct));
            await js.InvokeVoidAsync("labelUpEditor.setPrintCalibration", json);
        }
        catch (Exception ex)
        {
            EditorLog.Warn("인쇄 배율 보정 저장 실패: " + ex.Message);
        }
    }

    public Task ResetAsync() => SetAsync(100f, 100f);

    /// <summary>
    /// 설계 길이가 실제로 몇 mm 로 찍혔는지로 배율을 낸다. 271mm 로 나온 270mm 는
    /// 270/271 = 99.631% 로 줄여 보내야 270mm 가 된다. 사용자가 나눗셈 방향을
    /// 거꾸로 잡는 일이 없도록 자를 댄 값을 그대로 받는다.
    /// </summary>
    public static float PctFromMeasurement(float designMm, float measuredMm)
        => designMm > 0.01f && measuredMm > 0.01f ? Clamp(designMm / measuredMm * 100f) : 100f;

    private static bool IsNeutral(float pct) => Math.Abs(pct - 100f) < 0.0005f;

    private static float Clamp(float pct)
        => float.IsFinite(pct) ? Math.Clamp(pct, MinPct, MaxPct) : 100f;

    private sealed record Stored(float X, float Y);
}
