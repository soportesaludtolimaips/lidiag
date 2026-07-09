let initialized = false;

export function initWysiwyg(el) {
    try {
        const $root = el ? $(el) : $(document);
        $root.find(".textarea_editor").each((_, el) => {
            const $el = $(el);
            if ($el.data("wysihtml5")) return;
            $el.wysihtml5({
                stylesheets: ["/lib/css/wysiwyg-color.css"],
            });
        });
    } catch (e) {
        console.warn("Wysiwyg init error:", e);
    }
}

export function getWysiwygValue(el) {
    try {
        const $el = $(el).find("textarea.textarea_editor").first();
        if (!$el.length) return "";
        const inst = $el.data("wysihtml5");
        if (inst && inst.editor && typeof inst.editor.getValue === "function") {
            return inst.editor.getValue();
        }
        return $el.val() || "";
    } catch (_) {
        return "";
    }
}

export function isHtmlEffectivelyEmpty(html) {
    const text = String(html || "")
        .replace(/<[^>]*>/g, " ")
        .replace(/&nbsp;/g, " ")
        .replace(/\s+/g, " ")
        .trim();
    return !text;
}
