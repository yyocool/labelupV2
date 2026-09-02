using Microsoft.AspNetCore.Components.Web;
using Microsoft.AspNetCore.Components.WebAssembly.Hosting;
using LabelUp.Editor;
using LabelUp.Editor.Services;

var builder = WebAssemblyHostBuilder.CreateDefault(args);
builder.RootComponents.Add<App>("#app");
builder.RootComponents.Add<HeadOutlet>("head::after");

builder.Services.AddScoped(sp => new HttpClient { BaseAddress = new Uri(builder.HostEnvironment.BaseAddress) });
builder.Services.AddSingleton<EditorSession>();
builder.Services.AddSingleton<HistoryService>();
builder.Services.AddScoped<DraftStorage>();
builder.Services.AddScoped<ExportService>();
builder.Services.AddScoped<FontCatalog>();
builder.Services.AddScoped<PaperCatalog>();
builder.Services.AddScoped<DataImportService>();
builder.Services.AddScoped<ExternalImportService>();
builder.Services.AddScoped<EditorCloudStorage>();

await builder.Build().RunAsync();
