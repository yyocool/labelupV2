namespace LabelUp.Editor.Services;

public static class EditorLog
{
    public static void Info(string message) => Console.WriteLine($"[LabelUp] {message}");
    public static void Warn(string message) => Console.WriteLine($"[LabelUp:WARN] {message}");
    public static void Error(string message, Exception? ex = null)
        => Console.WriteLine(ex is null ? $"[LabelUp:ERR] {message}" : $"[LabelUp:ERR] {message} :: {ex}");
}
