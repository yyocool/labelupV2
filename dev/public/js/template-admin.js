(() => {
  const cfg = window.LABELUP_TEMPLATE_ADMIN || {};
  const urls = cfg.urls || {};
  const categories = cfg.categories || {};
  const items = cfg.items || [];
  const alertEl = document.getElementById("adminAlert");
  const form = document.getElementById("tplForm");

  function showAlert(msg, ok) {
    if (!alertEl) return;
    alertEl.textContent = msg;
    alertEl.className = `admin-alert ${ok ? "is-ok" : "is-err"}`;
    alertEl.style.display = "block";
  }

  function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.hidden = false;
  }
  function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.hidden = true;
  }

  document.querySelectorAll("[data-close]").forEach((btn) => {
    btn.addEventListener("click", () => closeModal(btn.getAttribute("data-close")));
  });

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function categoryOptions(selected) {
    return ['<option value="">카테고리 선택</option>']
      .concat(
        Object.keys(categories).map((key) => {
          const label = categories[key];
          return `<option value="${escapeHtml(key)}"${key === String(selected || "") ? " selected" : ""}>${escapeHtml(
            label
          )}</option>`;
        })
      )
      .join("");
  }

  function findItem(id) {
    return items.find((x) => Number(x.id) === Number(id)) || {};
  }

  function buildForm(row) {
    row = row || {};
    const json = row.document_json
      ? typeof row.document_json === "string"
        ? row.document_json
        : JSON.stringify(row.document_json, null, 2)
      : "";
    form.innerHTML = `
      <input type="hidden" name="id" value="${row.id || ""}">
      <div class="admin-form-grid">
        <label class="admin-field"><span>이름</span><input name="name" class="admin-input" required value="${escapeHtml(
          row.name || ""
        )}"></label>
        <label class="admin-field"><span>슬러그</span><input name="slug" class="admin-input" placeholder="food-honey" value="${escapeHtml(
          row.slug || ""
        )}"></label>
        <label class="admin-field"><span>카테고리</span><select name="category" class="admin-input" required>${categoryOptions(
          row.category
        )}</select></label>
        <label class="admin-field"><span>포인트 컬러</span><input name="tone" class="admin-input" value="${escapeHtml(
          row.tone || "#7B2840"
        )}"></label>
        <label class="admin-field"><span>용지번호</span><input name="paper_no" class="admin-input" value="${escapeHtml(
          row.paper_no || row.paperNo || ""
        )}"></label>
        <label class="admin-field"><span>라벨 폭 mm</span><input type="number" step="0.1" name="paper_w_mm" class="admin-input" value="${
          row.paper_w_mm || row.widthMm || 70
        }"></label>
        <label class="admin-field"><span>라벨 높이 mm</span><input type="number" step="0.1" name="paper_h_mm" class="admin-input" value="${
          row.paper_h_mm || row.heightMm || 36
        }"></label>
        <label class="admin-field"><span>형상</span>
          <select name="paper_shape" class="admin-input">
            <option value="rect"${(row.paper_shape || row.shape) === "rect" ? " selected" : ""}>사각형</option>
            <option value="roundrect"${(row.paper_shape || row.shape) === "roundrect" ? " selected" : ""}>라운드</option>
            <option value="ellipse"${(row.paper_shape || row.shape) === "ellipse" ? " selected" : ""}>원형</option>
          </select>
        </label>
        <label class="admin-field admin-field--full"><span>태그</span>
          <input name="tags" class="admin-input" placeholder="꿀,식품,원형" value="${escapeHtml(row.tags || "")}">
        </label>
        <label class="admin-field admin-field--full"><span>설명</span>
          <textarea name="description" class="admin-input" rows="2">${escapeHtml(row.description || "")}</textarea>
        </label>
        <label class="admin-field"><span>정렬</span><input type="number" name="sort_order" class="admin-input" value="${
          row.sort_order ?? 0
        }"></label>
        <label class="admin-field admin-check"><input type="checkbox" name="is_active" ${
          row.is_active === undefined || Number(row.is_active) ? "checked" : ""
        }> 노출</label>
        <label class="admin-field admin-field--full"><span>편집기 문서 JSON</span>
          <textarea name="document_json" class="admin-input" rows="10" placeholder="비우면 규격만으로 빈 문서를 생성합니다.">${escapeHtml(
            json
          )}</textarea>
          <small>LabelUp 편집기 document JSON. 시드로 만든 항목은 바로 편집 가능한 객체 데이터를 포함합니다.</small>
        </label>
      </div>`;
  }

  document.querySelector(".js-tpl-add")?.addEventListener("click", () => {
    document.getElementById("tplModalTitle").textContent = "템플릿 추가";
    buildForm({ is_active: 1, sort_order: 0, tone: "#7B2840", paper_shape: "rect" });
    openModal("tplModal");
  });

  document.querySelectorAll(".js-tpl-edit").forEach((btn) => {
    btn.addEventListener("click", () => {
      const card = btn.closest(".tpl-card");
      const meta = JSON.parse(card.getAttribute("data-row") || "{}");
      const full = findItem(meta.id);
      document.getElementById("tplModalTitle").textContent = "템플릿 수정";
      buildForm({ ...meta, ...full });
      openModal("tplModal");
    });
  });

  document.querySelectorAll(".js-tpl-preview").forEach((btn) => {
    btn.addEventListener("click", () => {
      const card = btn.closest(".tpl-card");
      const row = JSON.parse(card.getAttribute("data-row") || "{}");
      const title = document.getElementById("tplPreviewTitle");
      const box = document.getElementById("tplPreviewSvg");
      const meta = document.getElementById("tplPreviewMeta");
      if (title) title.textContent = row.name || "템플릿 미리보기";
      if (box) {
        const live = card.querySelector(".tpl-card-preview");
        box.innerHTML = live ? live.innerHTML : "";
        if (!box.innerHTML) {
          box.textContent = row.name || "미리보기 없음";
        }
      }
      if (meta) {
        const size = [row.paper_no || row.paperNo, `${row.paper_w_mm || row.widthMm || ""}×${row.paper_h_mm || row.heightMm || ""}mm`]
          .filter(Boolean)
          .join(" · ");
        meta.textContent = [row.categoryName || row.category, size].filter(Boolean).join(" · ");
      }
      openModal("tplPreviewModal");
    });
  });

  document.querySelectorAll(".js-tpl-delete").forEach((btn) => {
    btn.addEventListener("click", async () => {
      if (!confirm("이 템플릿을 삭제할까요?")) return;
      try {
        const res = await fetch(urls.delete, {
          method: "POST",
          credentials: "same-origin",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id: Number(btn.dataset.id) }),
        });
        const data = await res.json();
        if (!res.ok || data.success === false) throw new Error(data.message || "삭제 실패");
        location.reload();
      } catch (err) {
        showAlert(err.message || "삭제 오류", false);
      }
    });
  });

  form?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const fd = new FormData(form);
    const payload = {
      id: Number(fd.get("id") || 0) || 0,
      name: String(fd.get("name") || "").trim(),
      slug: String(fd.get("slug") || "").trim(),
      category: String(fd.get("category") || "").trim(),
      tags: String(fd.get("tags") || ""),
      description: String(fd.get("description") || ""),
      tone: String(fd.get("tone") || "#7B2840"),
      paper_no: String(fd.get("paper_no") || ""),
      paper_w_mm: Number(fd.get("paper_w_mm") || 70),
      paper_h_mm: Number(fd.get("paper_h_mm") || 36),
      paper_shape: String(fd.get("paper_shape") || "rect"),
      sort_order: Number(fd.get("sort_order") || 0),
      is_active: form.querySelector('[name="is_active"]')?.checked ? 1 : 0,
      document_json: String(fd.get("document_json") || "").trim(),
    };
    try {
      const res = await fetch(urls.save, {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      const data = await res.json();
      if (!res.ok || data.success === false) throw new Error(data.message || "저장 실패");
      location.reload();
    } catch (err) {
      showAlert(err.message || "저장 오류", false);
    }
  });

  document.querySelector(".js-tpl-seed")?.addEventListener("click", async () => {
    if (!confirm("기본 테마 50종을 반영할까요? 같은 슬러그는 덮어씁니다.")) return;
    try {
      showAlert("시드 반영 중…", true);
      const res = await fetch(urls.seed, {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ force: true }),
      });
      const data = await res.json();
      if (!res.ok || data.success === false) throw new Error(data.message || "시드 실패");
      showAlert(
        `시드 완료: 추가 ${data.data?.inserted || 0} · 갱신 ${data.data?.updated || 0} · 총 ${data.data?.total || 0}`,
        true
      );
      setTimeout(() => location.reload(), 800);
    } catch (err) {
      showAlert(err.message || "시드 오류", false);
    }
  });
})();
