using System.Reflection;

namespace LabelUp.Editor;

internal static class BuildInfo
{
    public static string Stamp { get; } = ReadStamp();

    private static string ReadStamp()
    {
        var stamp = typeof(BuildInfo).Assembly
            .GetCustomAttributes<AssemblyMetadataAttribute>()
            .FirstOrDefault(a => a.Key == "BuildStamp")
            ?.Value;
        return string.IsNullOrWhiteSpace(stamp)
            ? DateTime.Now.ToString("yyMMddHHmm")
            : stamp;
    }
}
