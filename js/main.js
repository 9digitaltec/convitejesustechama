/**
 * Script Principal da Landing Page — 24º Jesus te Chama
 *
 * Responsável por:
 * - Renderizar dinamicamente os cards de depoimentos em vídeo a partir de `testimonials`
 * - Aplicar lazy loading inteligente de metadados dos vídeos (sem autoplay)
 * - Conectar os eventos de tracking (page_view, vídeos, compartilhamento, WhatsApp)
 * - Gerenciar compartilhamento via Web Share API + fallback de área de transferência
 */

(function () {
  "use strict";

  /**
   * Formata o título amigável do depoimento para exibição e para o payload de tracking.
   */
  function getTestimonialDisplayName(item, index) {
    const numberStr = String(index + 1).padStart(2, "0");
    if (item.name && item.name.trim() !== "") {
      return item.name.trim();
    }
    return "Depoimento " + numberStr;
  }

  /**
   * Garante que apenas um vídeo (ou a música tema) toque por vez.
   */
  function pauseOtherVideos(currentMedia) {
    const allVideos = document.querySelectorAll(".testimonial-video");
    allVideos.forEach((v) => {
      if (v !== currentMedia && !v.paused) {
        v.pause();
      }
    });

    const themeAudio = document.getElementById("theme-audio");
    if (themeAudio && themeAudio !== currentMedia && !themeAudio.paused) {
      themeAudio.pause();
    }
  }

  /**
   * Configura IntersectionObserver para carregar os metadados dos vídeos
   * somente quando estiverem próximos da área visível da tela.
   */
  function setupVideoLazyMetadata(videoElements) {
    if (!("IntersectionObserver" in window)) {
      videoElements.forEach((v) => {
        if (v.getAttribute("preload") === "none") {
          v.setAttribute("preload", "metadata");
        }
      });
      return;
    }

    const observer = new IntersectionObserver(
      (entries, obs) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const video = entry.target;
            if (video.getAttribute("preload") === "none") {
              video.setAttribute("preload", "metadata");
            }
            obs.unobserve(video);
          }
        });
      },
      { rootMargin: "250px 0px" }
    );

    videoElements.forEach((video) => observer.observe(video));
  }

  /**
   * Busca automaticamente os vídeos presentes na pasta /depoimentos/ via API PHP
   * e mescla com configurações opcionais definidas em `window.testimonials` (js/config.js).
   */
  async function resolveTestimonialsList() {
    const manualList = Array.isArray(window.testimonials) ? window.testimonials : [];
    const manualByFile = new Map();
    manualList.forEach((item) => {
      if (item && item.file) {
        manualByFile.set(item.file.trim(), item);
      }
    });

    try {
      const response = await fetch("api/testimonials.php", {
        method: "GET",
        headers: { Accept: "application/json" },
        cache: "no-store"
      });
      if (response.ok) {
        const data = await response.json();
        if (data && Array.isArray(data.testimonials) && data.testimonials.length > 0) {
          return data.testimonials.map((autoItem, idx) => {
            const numStr = String(idx + 1).padStart(2, "0");
            const manual = manualByFile.get(autoItem.file);
            if (manual) {
              return {
                id: manual.id || autoItem.id || "depoimento_" + numStr,
                name: manual.name || autoItem.name || "",
                role: manual.role || autoItem.role || "",
                file: autoItem.file,
                poster: manual.poster || autoItem.poster || "",
                caption:
                  manual.caption !== undefined && manual.caption !== ""
                    ? manual.caption
                    : autoItem.caption || "",
                tracks: Array.isArray(manual.tracks) ? manual.tracks : []
              };
            }
            return autoItem;
          });
        }
      }
    } catch (err) {
      // Se estiver rodando sem PHP (ex: abrindo o arquivo direto), usa a lista de js/config.js
    }

    return manualList;
  }

  /**
   * Renderiza os cards de depoimentos dentro de #testimonials-list
   */
  async function renderTestimonials() {
    const container = document.getElementById("testimonials-list");
    if (!container) return;

    const list = await resolveTestimonialsList();
    container.innerHTML = "";

    if (list.length === 0) {
      return;
    }

    const createdVideos = [];

    list.forEach((item, index) => {
      const displayName = getTestimonialDisplayName(item, index);
      const hasCustomName = Boolean(item.name && item.name.trim() !== "");
      const hasRole = Boolean(item.role && item.role.trim() !== "");
      const hasCaption = Boolean(item.caption && item.caption.trim() !== "");
      const numberLabel = String(index + 1).padStart(2, "0");

      const article = document.createElement("article");
      article.className = "testimonial-card";
      article.setAttribute("data-video-id", item.id || "depoimento_" + numberLabel);

      // Cabeçalho do card
      const header = document.createElement("div");
      header.className = "testimonial-header";

      const badge = document.createElement("span");
      badge.className = "testimonial-badge";
      badge.textContent = list.length > 1 ? "Mensagem #" + numberLabel : "Mensagem da Equipe";
      header.appendChild(badge);

      const metaBox = document.createElement("div");
      metaBox.className = "testimonial-person";

      const titleEl = document.createElement("h3");
      titleEl.className = "testimonial-name";
      titleEl.textContent = hasCustomName ? item.name.trim() : "Convite da nossa equipe";
      metaBox.appendChild(titleEl);

      if (hasRole) {
        const roleEl = document.createElement("p");
        roleEl.className = "testimonial-role";
        roleEl.textContent = item.role.trim();
        metaBox.appendChild(roleEl);
      } else {
        const roleEl = document.createElement("p");
        roleEl.className = "testimonial-role";
        roleEl.textContent = "Equipe 24º Jesus te Chama";
        metaBox.appendChild(roleEl);
      }

      header.appendChild(metaBox);
      article.appendChild(header);

      // Container do player de vídeo
      const videoWrapper = document.createElement("div");
      videoWrapper.className = "video-wrapper";

      const video = document.createElement("video");
      video.className = "testimonial-video";
      video.controls = true;
      video.playsInline = true;
      video.setAttribute("playsinline", "");
      video.setAttribute("webkit-playsinline", "");
      // Primeiro vídeo usa preload="metadata", demais começam com preload="none" e ativam via IntersectionObserver
      video.setAttribute("preload", index === 0 ? "metadata" : "none");
      video.setAttribute(
        "aria-label",
        hasCustomName
          ? "Vídeo de depoimento de " + item.name.trim()
          : "Vídeo de depoimento da equipe do 24º Jesus te Chama"
      );

      if (item.poster && item.poster.trim() !== "") {
        video.setAttribute("poster", item.poster.trim());
      }

      const source = document.createElement("source");
      // Se não houver poster personalizado, #t=0.1 ajuda navegadores mobile a exibirem o primeiro frame
      const videoSrc =
        !item.poster && item.file && item.file.indexOf("#") === -1
          ? item.file + "#t=0.1"
          : item.file;
      source.src = videoSrc;
      source.type = "video/mp4";
      video.appendChild(source);

      // Suporte preparado para legendas WebVTT (.vtt)
      if (Array.isArray(item.tracks)) {
        item.tracks.forEach((trackCfg, tIdx) => {
          if (!trackCfg || !trackCfg.src) return;
          const track = document.createElement("track");
          track.kind = trackCfg.kind || "captions";
          track.label = trackCfg.label || "Português";
          track.srclang = trackCfg.srclang || "pt-BR";
          track.src = trackCfg.src;
          if (tIdx === 0 || trackCfg.default) {
            track.default = true;
          }
          video.appendChild(track);
        });
      }

      const fallbackText = document.createElement("p");
      fallbackText.className = "video-fallback";
      fallbackText.textContent =
        "Seu navegador não suporta a reprodução direta deste vídeo.";
      video.appendChild(fallbackText);

      // Eventos de UI no player
      video.addEventListener("play", () => {
        article.classList.add("is-playing");
        pauseOtherVideos(video);
      });

      video.addEventListener("pause", () => {
        article.classList.remove("is-playing");
      });

      video.addEventListener("ended", () => {
        article.classList.remove("is-playing");
        article.classList.add("is-completed");
      });

      videoWrapper.appendChild(video);
      article.appendChild(videoWrapper);

      // Legenda curta opcional abaixo do vídeo
      if (hasCaption) {
        const captionEl = document.createElement("p");
        captionEl.className = "testimonial-caption";
        captionEl.textContent = item.caption.trim();
        article.appendChild(captionEl);
      }

      container.appendChild(article);
      createdVideos.push(video);

      // Conecta rastreamento anônimo deste vídeo
      if (typeof window.bindVideoTracking === "function") {
        window.bindVideoTracking(video, {
          id: item.id || "depoimento_" + numberLabel,
          displayName: item.adminLabel || displayName
        });
      }
    });

    setupVideoLazyMetadata(createdVideos);
  }

  /**
   * Copia texto para a área de transferência com fallback compatível.
   */
  function copyTextToClipboard(text) {
    if (navigator.clipboard && typeof navigator.clipboard.writeText === "function") {
      return navigator.clipboard.writeText(text).then(
        () => true,
        () => fallbackCopyText(text)
      );
    }
    return Promise.resolve(fallbackCopyText(text));
  }

  function fallbackCopyText(text) {
    try {
      const textArea = document.createElement("textarea");
      textArea.value = text;
      textArea.setAttribute("readonly", "");
      textArea.style.position = "fixed";
      textArea.style.top = "-9999px";
      textArea.style.opacity = "0";
      document.body.appendChild(textArea);
      textArea.select();
      textArea.setSelectionRange(0, text.length);
      const copied = document.execCommand("copy");
      document.body.removeChild(textArea);
      return copied;
    } catch (err) {
      return false;
    }
  }

  /**
   * Exibe o feedback "Link copiado!" de forma acessível.
   */
  let feedbackTimer = null;
  function showShareFeedback(message) {
    const feedbackEl = document.getElementById("share-feedback");
    if (!feedbackEl) return;

    feedbackEl.textContent = message || "Link copiado!";
    feedbackEl.hidden = false;
    feedbackEl.classList.add("is-visible");

    if (feedbackTimer) {
      clearTimeout(feedbackTimer);
    }
    feedbackTimer = setTimeout(() => {
      feedbackEl.classList.remove("is-visible");
      setTimeout(() => {
        feedbackEl.hidden = true;
      }, 250);
    }, 3200);
  }

  /**
   * Configura o rastreamento do botão de inscrição ("Quero participar • Preencher ficha")
   * e mantém suporte caso #btn-share exista.
   */
  function setupShareButton() {
    const participateBtn = document.getElementById("btn-participate");
    if (participateBtn) {
      if (window.APP_CONFIG && window.APP_CONFIG.formUrl) {
        participateBtn.href = window.APP_CONFIG.formUrl;
      }
      participateBtn.addEventListener("click", () => {
        if (typeof window.trackEvent === "function") {
          window.trackEvent("share_click");
        }
      });
    }

    const shareBtn = document.getElementById("btn-share");
    if (!shareBtn) return;

    shareBtn.addEventListener("click", async () => {
      if (typeof window.trackEvent === "function") {
        window.trackEvent("share_click");
      }

      const shareUrl = window.location.href;
      const shareData = {
        title:
          (window.APP_CONFIG && window.APP_CONFIG.shareTitle) ||
          "24º Jesus te Chama | É necessário nascer de novo",
        text:
          (window.APP_CONFIG && window.APP_CONFIG.shareText) ||
          "Um convite especial para você: 24º Jesus te Chama em Pinhais (30 e 31 de outubro e 1º de novembro).",
        url: shareUrl
      };

      // Tenta usar Web Share API nativa no celular
      if (typeof navigator.share === "function") {
        try {
          await navigator.share(shareData);
          return;
        } catch (err) {
          // Se o usuário apenas cancelou a gaveta nativa, não faz nada
          if (err && err.name === "AbortError") {
            return;
          }
          // Se falhou por outro motivo, cai para o fallback de copiar o link
        }
      }

      await copyTextToClipboard(shareUrl);
      showShareFeedback("Link copiado!");
    });
  }

  /**
   * Configura o player da música tema do retiro.
   */
  function setupThemeMusic() {
    const audio = document.getElementById("theme-audio");
    const musicCard = document.getElementById("music-card");
    if (!audio) return;

    audio.addEventListener("play", () => {
      pauseOtherVideos(audio);
      if (musicCard) {
        musicCard.classList.add("is-playing");
      }
    });

    audio.addEventListener("pause", () => {
      if (musicCard) {
        musicCard.classList.remove("is-playing");
      }
    });

    audio.addEventListener("ended", () => {
      if (musicCard) {
        musicCard.classList.remove("is-playing");
      }
    });
  }

  /**
   * Inicialização geral ao carregar o DOM
   */
  function init() {
    if (typeof window.trackPageViewOnce === "function") {
      window.trackPageViewOnce();
    }

    renderTestimonials();
    setupThemeMusic();
    setupShareButton();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
