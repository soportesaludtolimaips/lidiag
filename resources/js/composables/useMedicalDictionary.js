import medicalTermsRaw from "@/data/terminos_medicos.txt?raw";
import medicalReplacements from "@/data/medical_replacements.json";

const MEDICAL_REPLACEMENTS = Array.isArray(medicalReplacements)
    ? medicalReplacements
    : [];

console.log('[MedicalDictionary] Replacements cargados:', MEDICAL_REPLACEMENTS.length);

function stripDiacritics(text) {
    try {
        return String(text)
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "");
    } catch (e) {
        return String(text);
    }
}

function escapeRegExp(text) {
    return String(text).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

function normalizeWord(text) {
    return stripDiacritics(String(text))
        .toLowerCase()
        .replace(/[^a-z0-9ñ]+/gi, "");
}

function compileReplacements(rules) {
    const compiled = [];
    if (!Array.isArray(rules)) return compiled;

    rules.forEach((rule) => {
        if (!Array.isArray(rule) || rule.length < 3) return;
        const [regexString, flags, replacement] = rule;
        try {
            compiled.push([
                new RegExp(String(regexString), String(flags || "")),
                String(replacement),
            ]);
        } catch (e) { }
    });

    return compiled;
}

function levenshteinMax(a, b, maxDist = 2) {
    if (a === b) return 0;
    if (!a || !b) return Math.max(a ? a.length : 0, b ? b.length : 0);

    const la = a.length;
    const lb = b.length;
    if (Math.abs(la - lb) > maxDist) return maxDist + 1;

    let prev = new Array(lb + 1);
    let curr = new Array(lb + 1);
    for (let j = 0; j <= lb; j++) prev[j] = j;

    for (let i = 1; i <= la; i++) {
        curr[0] = i;
        let minInRow = curr[0];
        const ca = a.charCodeAt(i - 1);
        for (let j = 1; j <= lb; j++) {
            const cost = ca === b.charCodeAt(j - 1) ? 0 : 1;
            const v = Math.min(
                prev[j] + 1,
                curr[j - 1] + 1,
                prev[j - 1] + cost
            );
            curr[j] = v;
            if (v < minInRow) minInRow = v;
        }
        if (minInRow > maxDist) return maxDist + 1;
        const tmp = prev;
        prev = curr;
        curr = tmp;
    }
    return prev[lb];
}

export function useMedicalDictionary() {
    let _compiled = null;
    let _vocabMap = null;
    let _vocabKeys = null;
    let _vocabIndex = null;
    let _extraTerms = [];

    function getCompiledConfig() {
        if (_compiled) return _compiled;

        _compiled = {
            replacements: compileReplacements(MEDICAL_REPLACEMENTS),
        };

        return _compiled;
    }

    function setExtraTerms(terms) {
        _extraTerms = Array.isArray(terms) ? terms : [];
        rebuildVocabulary();
    }

    function addExtraTerms(terms) {
        if (!Array.isArray(terms)) return;
        _extraTerms = [..._extraTerms, ...terms];
        rebuildVocabulary();
    }

    function rebuildVocabulary(extraPhrases = []) {
        const map = {};

        const addPhrase = (phrase) => {
            if (!phrase) return;
            const s = String(phrase).trim();
            if (!s) return;

            const nFull = normalizeWord(s);
            if (nFull) map[nFull] = s;

            const tokens = s.split(/[\s/,-]+/g).filter(Boolean);
            tokens.forEach((t) => {
                const nt = normalizeWord(t);
                if (nt && nt.length >= 3 && !map[nt]) map[nt] = t;
            });
        };

        _extraTerms.forEach((t) => addPhrase(t));

        if (Array.isArray(extraPhrases) && extraPhrases.length > 0) {
            console.log('[MedicalDictionary] Frases adicionales:', extraPhrases);
            extraPhrases.forEach((p) => addPhrase(p));
        }

        _vocabMap = map;
        _vocabKeys = Object.keys(map);
        console.log(`[MedicalDictionary] Vocabulario: ${_vocabKeys.length} términos`);

        const index = {};
        _vocabKeys.forEach((k) => {
            if (!k) return;
            const first = k[0];
            const len = k.length;
            if (!index[first]) index[first] = {};
            if (!index[first][len]) index[first][len] = [];
            index[first][len].push(k);
        });
        _vocabIndex = index;
    }

    function applyReplacements(text) {
        if (!text) return text;
        let out = String(text);

        const compiled = getCompiledConfig();
        if (compiled && Array.isArray(compiled.replacements)) {
            compiled.replacements.forEach(([re, rep]) => {
                out = out.replace(re, rep);
            });
        }

        return out;
    }

    function correctText(text, { uppercase = false } = {}) {
        if (!text) return text;
        let out = applyReplacements(text);

        if (!_vocabKeys || !_vocabKeys.length) {
            console.warn('[MedicalDictionary] Vocabulario vacío, sin correcciones');
            return uppercase ? out.toUpperCase() : out;
        }

        let correctionsCount = 0;

        out = out.replace(/[\p{L}\p{N}]+/gu, (token) => {
            if (/^\d+$/u.test(token)) return token;
            if (token.length < 3) return token;

            const n = normalizeWord(token);
            if (!n) return token;

            if (_vocabMap[n]) {
                const canonical = _vocabMap[n];
                correctionsCount++;
                console.log(`[MedicalDictionary] Exact match: "${token}" -> "${canonical}"`);
                return uppercase ? String(canonical).toUpperCase() : String(canonical);
            }

            const first = n[0];
            const bucketByLen = _vocabIndex[first] || null;
            let best = null;
            let bestDist = 3;

            if (bucketByLen) {
                for (let L = n.length - 2; L <= n.length + 2; L++) {
                    const arr = bucketByLen[L];
                    if (!arr || !arr.length) continue;
                    for (let i = 0; i < arr.length; i++) {
                        const k = arr[i];
                        const d = levenshteinMax(n, k, 2);
                        if (d < bestDist) {
                            bestDist = d;
                            best = k;
                            if (bestDist === 1) break;
                        }
                    }
                    if (bestDist === 1) break;
                }
            }

            if (best && bestDist <= 2 && _vocabMap[best]) {
                const canonical = _vocabMap[best];
                correctionsCount++;
                console.log(`[MedicalDictionary] Fuzzy match: "${token}" -> "${canonical}" (dist=${bestDist})`);
                return uppercase ? String(canonical).toUpperCase() : String(canonical);
            }

            return token;
        });

        if (correctionsCount > 0) {
            console.log(`[MedicalDictionary] Total correcciones aplicadas: ${correctionsCount}`);
        }

        return uppercase ? out.toUpperCase() : out;
    }

    function getTerms() {
        return [...MEDICAL_TERMS];
    }

    function getReplacements() {
        return MEDICAL_REPLACEMENTS;
    }

    function getVocabMap() {
        return _vocabMap || {};
    }

    function getVocabKeys() {
        return _vocabKeys || [];
    }

    return {
        setExtraTerms,
        addExtraTerms,
        rebuildVocabulary,
        applyReplacements,
        correctText,
        getTerms,
        getReplacements,
        getVocabMap,
        getVocabKeys,
        stripDiacritics,
        normalizeWord,
        escapeRegExp,
    };
}

export { stripDiacritics, normalizeWord, escapeRegExp };
