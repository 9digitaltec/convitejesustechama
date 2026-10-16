/**
 * Configuração Central — 24º Jesus te Chama
 *
 * IMPORTANTE:
 * - NUNCA armazene senhas, credenciais de banco ou segredos neste arquivo.
 * - Com DEBUG_TRACKING = false, os eventos são enviados para `api/track.php` e salvos no MySQL.
 * - Se quiser testar apenas no console sem enviar para o banco, altere DEBUG_TRACKING para true.
 */

const DEBUG_TRACKING = false;

const APP_CONFIG = {
  debugTracking: DEBUG_TRACKING,
  trackingEndpoint: "api/track.php",
  eventTitle: "24º Jesus te Chama",
  eventTheme: "É necessário nascer de novo",
  eventDates: "30 e 31 de outubro e 1º de novembro",
  eventLocation: "Pinhais",
  eventAudience: "Jovens a partir de 17 anos e adultos.",
  formUrl: "https://docs.google.com/forms/d/e/1FAIpQLSehyiXCgNbepo4s-Dr_lfMMFKakipOildw8CYx1_i5hJM6rZA/viewform?usp=header",
  shareTitle: "24º Jesus te Chama | É necessário nascer de novo",
  shareText:
    "Um convite especial para você: 24º Jesus te Chama — 30 e 31 de outubro e 1º de novembro em Pinhais. Assista ao recado da nossa equipe:"
};

/**
 * Lista de Depoimentos em Vídeo
 *
 * Estrutura pronta para receber entre 4 e 5 vídeos.
 * Campos suportados em cada item:
 * - id: identificador único usado no banco/tracking (ex: "depoimento_01")
 * - name: nome do integrante da equipe (opcional, ex: "Lucas")
 * - role: função ou equipe no retiro (opcional, ex: "Equipe de Acolhida")
 * - file: caminho relativo do arquivo MP4 (ex: "depoimentos/video.mp4")
 * - poster: caminho relativo da imagem de capa opcional (ex: "imagens/poster-01.jpg")
 * - caption: legenda curta opcional exibida abaixo do vídeo
 * - tracks: lista opcional de arquivos WebVTT (.vtt) para legendas acessíveis
 */
const testimonials = [
  {
    id: "depoimento_01",
    name: "",
    role: "",
    file: "depoimentos/WhatsApp Video 2026-10-13 at 09.26.29.mp4",
    poster: "",
    caption: "Um convite preparado de coração por quem está organizando cada detalhe para receber você.",
    tracks: []
  }

  /* =========================================================================
   * EXEMPLOS PRONTOS PARA ADICIONAR OS PRÓXIMOS VÍDEOS (2 a 5):
   * Basta descomentar e preencher o arquivo na pasta /depoimentos/
   * =========================================================================
  ,
  {
    id: "depoimento_02",
    name: "Nome do Jovem",
    role: "Equipe do 24º JTC",
    file: "depoimentos/depoimento-02.mp4",
    poster: "",
    caption: "",
    tracks: []
  },
  {
    id: "depoimento_03",
    name: "Nome do Jovem",
    role: "Equipe do 24º JTC",
    file: "depoimentos/depoimento-03.mp4",
    poster: "",
    caption: "",
    tracks: []
  },
  {
    id: "depoimento_04",
    name: "Nome do Jovem",
    role: "Equipe do 24º JTC",
    file: "depoimentos/depoimento-04.mp4",
    poster: "",
    caption: "",
    tracks: []
  },
  {
    id: "depoimento_05",
    name: "Nome do Jovem",
    role: "Equipe do 24º JTC",
    file: "depoimentos/depoimento-05.mp4",
    poster: "",
    caption: "",
    tracks: []
  }
  */
];

window.DEBUG_TRACKING = DEBUG_TRACKING;
window.APP_CONFIG = APP_CONFIG;
window.testimonials = testimonials;
