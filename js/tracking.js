/**
 * Sistema de Rastreamento Anônimo — 24º Jesus te Chama
 *
 * Coleta métricas estatísticas anônimas (sem dados pessoais ou fingerprinting)
 * sobre visualização da landing e retenção dos vídeos de depoimento.
 */

const VISITOR_STORAGE_KEY = "jtc_visitor_id";
const SESSION_STORAGE_KEY = "jtc_session_id";
const MILESTONES_STORAGE_KEY = "jtc_video_milestones";

const ALLOWED_EVENTS = new Set([
  "page_view",
  "video_start",
  "video_25",
  "video_50",
  "video_75",
  "video_90",
  "video_complete",
  "share_click",
  "whatsapp_click"
]);

// Estrutura em memória para evitar eventos duplicados na mesma sessão
const videoMilestones = {};

let memoryVisitorId = null;
let memorySessionId = null;
let pageViewSent = false;

/**
 * Gera um UUID v4 anônimo usando crypto.randomUUID()
 * com fallback seguro para navegadores mais antigos.
 */
function generateAnonymousId() {
  if (typeof crypto !== "undefined" && typeof crypto.randomUUID === "function") {
    try {
      return crypto.randomUUID();
    } catch (e) {
      // Continua para o fallback caso contexto não seja seguro (ex: file:// ou HTTP antigo)
    }
  }

  if (typeof crypto !== "undefined" && typeof crypto.getRandomValues === "function") {
    const bytes = new Uint8Array(16);
    crypto.getRandomValues(bytes);
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = Array.from(bytes, (b) => b.toString(16).padStart(2, "0")).join("");
    return (
      hex.slice(0, 8) +
      "-" +
      hex.slice(8, 12) +
      "-" +
      hex.slice(12, 16) +
      "-" +
      hex.slice(16, 20) +
      "-" +
      hex.slice(20, 32)
    );
  }

  // Fallback final baseado em timestamp + valores aleatórios
  const randomPart = Math.random().toString(36).substring(2, 12);
  const randomPart2 = Math.random().toString(36).substring(2, 12);
  return "jtc-" + Date.now().toString(36) + "-" + randomPart + randomPart2;
}

/**
 * Obtém ou cria o visitor_id persistido no localStorage.
 */
function getVisitorId() {
  if (memoryVisitorId) {
    return memoryVisitorId;
  }

  try {
    let visitorId = localStorage.getItem(VISITOR_STORAGE_KEY);
    if (!visitorId) {
      visitorId = generateAnonymousId();
      localStorage.setItem(VISITOR_STORAGE_KEY, visitorId);
    }
    memoryVisitorId = visitorId;
    return visitorId;
  } catch (err) {
    memoryVisitorId = generateAnonymousId();
    return memoryVisitorId;
  }
}

/**
 * Obtém ou cria o session_id persistido no sessionStorage.
 */
function getSessionId() {
  if (memorySessionId) {
    return memorySessionId;
  }

  try {
    const sessionId =
      sessionStorage.getItem(SESSION_STORAGE_KEY) || generateAnonymousId();
    sessionStorage.setItem(SESSION_STORAGE_KEY, sessionId);
    memorySessionId = sessionId;
    return sessionId;
  } catch (err) {
    memorySessionId = generateAnonymousId();
    return memorySessionId;
  }
}

/**
 * Restaura marcos de vídeo já registrados na sessão atual (se houver).
 */
function loadSessionMilestones() {
  try {
    const raw = sessionStorage.getItem(MILESTONES_STORAGE_KEY);
    if (!raw) return;
    const parsed = JSON.parse(raw);
    if (parsed && typeof parsed === "object") {
      Object.keys(parsed).forEach((videoId) => {
        if (Array.isArray(parsed[videoId])) {
          videoMilestones[videoId] = new Set(parsed[videoId]);
        }
      });
    }
  } catch (err) {
    // Ignora falhas de leitura do sessionStorage
  }
}

/**
 * Salva os marcos de vídeo no sessionStorage para consistência na sessão.
 */
function saveSessionMilestones() {
  try {
    const serializable = {};
    Object.keys(videoMilestones).forEach((videoId) => {
      serializable[videoId] = Array.from(videoMilestones[videoId]);
    });
    sessionStorage.setItem(MILESTONES_STORAGE_KEY, JSON.stringify(serializable));
  } catch (err) {
    // Ignora falhas de escrita no sessionStorage
  }
}

/**
 * Verifica se um marco de vídeo já foi enviado nesta sessão.
 */
function hasMilestone(videoId, eventName) {
  if (!videoMilestones[videoId]) {
    videoMilestones[videoId] = new Set();
  }
  return videoMilestones[videoId].has(eventName);
}

/**
 * Marca um evento de vídeo como já enviado nesta sessão.
 */
function markMilestone(videoId, eventName) {
  if (!videoMilestones[videoId]) {
    videoMilestones[videoId] = new Set();
  }
  videoMilestones[videoId].add(eventName);
  saveSessionMilestones();
}

/**
 * Função central de tracking.
 * Todos os eventos da página passam por aqui.
 *
 * @param {string} eventName Nome do evento permitido na whitelist
 * @param {object} data Dados opcionais (video_id, video_name, progress)
 */
function trackEvent(eventName, data = {}) {
  if (!ALLOWED_EVENTS.has(eventName)) {
    return Promise.resolve({ success: false, skipped: true });
  }

  const visitorId = getVisitorId();
  const sessionId = getSessionId();

  const payload = {
    visitor_id: visitorId,
    session_id: sessionId,
    event_name: eventName,
    video_id: data.video_id || null,
    video_name: data.video_name || null,
    progress:
      typeof data.progress === "number" && !Number.isNaN(data.progress)
        ? Math.max(0, Math.min(100, Math.round(data.progress)))
        : null,
    page_url: window.location.href.slice(0, 500),
    referrer: (document.referrer || "").slice(0, 500),
    screen_width: window.screen ? window.screen.width : window.innerWidth || 0,
    screen_height: window.screen ? window.screen.height : window.innerHeight || 0,
    language: (navigator.language || "pt-BR").slice(0, 20)
  };

  const isDebug =
    (typeof window.DEBUG_TRACKING !== "undefined" && window.DEBUG_TRACKING === true) ||
    (window.APP_CONFIG && window.APP_CONFIG.debugTracking === true);

  if (isDebug) {
    console.log("[TRACK]", eventName, payload);
    return Promise.resolve({ success: true, debug: true, payload });
  }

  const endpoint =
    (window.APP_CONFIG && window.APP_CONFIG.trackingEndpoint) || "api/track.php";

  try {
    return fetch(endpoint, {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify(payload),
      keepalive: true
    })
      .then((response) => {
        if (!response.ok) {
          return { success: false, status: response.status };
        }
        return response.json().catch(() => ({ success: true }));
      })
      .catch(() => {
        // Silencia erros de rede para nunca interromper a experiência do visitante
        return { success: false };
      });
  } catch (err) {
    return Promise.resolve({ success: false });
  }
}

/**
 * Registra o evento page_view apenas uma única vez por carregamento real da página.
 */
function trackPageViewOnce() {
  if (pageViewSent) {
    return;
  }
  pageViewSent = true;
  trackEvent("page_view");
}

/**
 * Vincula os listeners de progresso (play, timeupdate, ended) a um elemento <video>.
 * Garante que cada marco (video_start, 25%, 50%, 75%, 90%, video_complete)
 * seja disparado apenas UMA VEZ por sessão por vídeo.
 *
 * @param {HTMLVideoElement} videoEl Elemento de vídeo HTML5
 * @param {object} meta Metadados do vídeo ({ id, displayName })
 */
function bindVideoTracking(videoEl, meta) {
  if (!videoEl || !meta || !meta.id) return;

  const videoId = String(meta.id);
  const videoName = String(meta.displayName || meta.name || meta.id);

  if (!videoMilestones[videoId]) {
    videoMilestones[videoId] = new Set();
  }

  const progressSteps = [
    { threshold: 25, eventName: "video_25" },
    { threshold: 50, eventName: "video_50" },
    { threshold: 75, eventName: "video_75" },
    { threshold: 90, eventName: "video_90" }
  ];

  // 1. Primeiro play -> video_start
  videoEl.addEventListener("play", () => {
    if (!hasMilestone(videoId, "video_start")) {
      markMilestone(videoId, "video_start");
      trackEvent("video_start", {
        video_id: videoId,
        video_name: videoName,
        progress: 0
      });
    }
  });

  // 2. Progresso -> 25%, 50%, 75%, 90%
  videoEl.addEventListener("timeupdate", () => {
    const duration = videoEl.duration;
    if (!duration || !Number.isFinite(duration) || duration <= 0) {
      return;
    }

    const percent = (videoEl.currentTime / duration) * 100;

    for (let i = 0; i < progressSteps.length; i++) {
      const step = progressSteps[i];
      if (percent >= step.threshold && !hasMilestone(videoId, step.eventName)) {
        markMilestone(videoId, step.eventName);
        trackEvent(step.eventName, {
          video_id: videoId,
          video_name: videoName,
          progress: step.threshold
        });
      }
    }
  });

  // 3. Conclusão -> video_complete
  videoEl.addEventListener("ended", () => {
    // Garante que caso o timeupdate tenha saltado os últimos ms antes de ended, 90% conste
    for (let i = 0; i < progressSteps.length; i++) {
      const step = progressSteps[i];
      if (!hasMilestone(videoId, step.eventName)) {
        markMilestone(videoId, step.eventName);
        trackEvent(step.eventName, {
          video_id: videoId,
          video_name: videoName,
          progress: step.threshold
        });
      }
    }

    if (!hasMilestone(videoId, "video_complete")) {
      markMilestone(videoId, "video_complete");
      trackEvent("video_complete", {
        video_id: videoId,
        video_name: videoName,
        progress: 100
      });
    }
  });
}

// Inicializa o estado da sessão
loadSessionMilestones();
getVisitorId();
getSessionId();

// Expõe funções globalmente para uso em main.js e inspeção
window.trackEvent = trackEvent;
window.trackPageViewOnce = trackPageViewOnce;
window.bindVideoTracking = bindVideoTracking;
window.getVisitorId = getVisitorId;
window.getSessionId = getSessionId;
window.videoMilestones = videoMilestones;
