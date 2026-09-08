namespace LabelUp.Editor.Models;

public sealed class ShopClipartCatalogDto
{
    public List<ShopClipartItem> Items { get; set; } = [];
    public List<ShopClipartCategory> Categories { get; set; } = [];
    public int Total { get; set; }
    public int Page { get; set; } = 1;
    public int Pages { get; set; } = 1;
    public int PerPage { get; set; } = 48;
    public bool HasMore { get; set; }
}

public sealed class ShopClipartCategory
{
    public int Id { get; set; }
    public string Name { get; set; } = "";
    public string Slug { get; set; } = "";
}

public sealed class ShopClipartItem
{
    public int Id { get; set; }
    public string Title { get; set; } = "";
    public string ImageUrl { get; set; } = "";
    public int? CategoryId { get; set; }
    public string? CategoryName { get; set; }
    public string? CategorySlug { get; set; }
    public string? Hashtags { get; set; }
    public string? Description { get; set; }
}

public sealed class UserClipartCatalogDto
{
    public List<UserClipartItem> Items { get; set; } = [];
    public bool LoggedIn { get; set; }
}

public sealed class UserClipartItem
{
    public int Id { get; set; }
    public string Title { get; set; } = "";
    public string ImageUrl { get; set; } = "";
    public string? Prompt { get; set; }
    public string? CreatedAt { get; set; }
}
