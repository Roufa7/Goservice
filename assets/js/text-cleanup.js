(() => {
  const lang = (document.documentElement.getAttribute('lang') || 'fr').slice(0, 2);
  const labels = {
    forumTitle: lang === 'en' ? 'Forum & exchanges' : (lang === 'ar' ? 'المنتدى والتبادل' : 'Forum & echanges'),
    forumSub: lang === 'en' ? 'Publish, share images or videos, comment, like and follow discussions in a modern social interface.' : (lang === 'ar' ? 'انشر وشارك الصور أو الفيديوهات وتابع النقاشات في واجهة اجتماعية حديثة.' : 'Publiez, partagez des images ou videos, commentez, aimez et suivez les discussions dans une interface moderne inspiree des reseaux sociaux.'),
    saved: lang === 'en' ? 'Saved posts' : (lang === 'ar' ? 'المنشورات المحفوظة' : 'Posts enregistres'),
    recent: lang === 'en' ? 'Most recent' : (lang === 'ar' ? 'الأحدث' : 'Plus recents'),
    noResults: lang === 'en' ? 'No results' : (lang === 'ar' ? 'لا توجد نتائج' : 'Aucun resultat'),
    noPost: lang === 'en' ? 'No post found.' : (lang === 'ar' ? 'لم يتم العثور على منشور.' : 'Aucun post trouve.'),
    writePost: lang === 'en' ? 'Write a post...' : (lang === 'ar' ? 'اكتب منشورا...' : 'Publier un post...'),
    reply: lang === 'en' ? 'Reply' : (lang === 'ar' ? 'رد' : 'Repondre')
  };
  const exact = new Map([
    ['Forum & ÃƒÂ©changes', labels.forumTitle], ['Forum & Ã©changes', labels.forumTitle], ['Forum & ÃƒÆ’Ã‚Â©changes', labels.forumTitle],
    ['Posts enregistrÃƒÂ©s', labels.saved], ['Posts enregistrÃ©s', labels.saved], ['Posts enregistrés', labels.saved],
    ['Plus rÃƒÂ©cents', labels.recent], ['Plus rÃ©cents', labels.recent], ['Plus récents', labels.recent],
    ['Aucun rÃƒÂ©sultat', labels.noResults], ['Aucun rÃ©sultat', labels.noResults], ['Aucun résultat', labels.noResults],
    ['Aucun post trouvÃƒÂ©.', labels.noPost], ['Aucun post trouvÃ©.', labels.noPost], ['Aucun post trouvé.', labels.noPost],
    ['Publier un post...', labels.writePost], ['Ecrire un commentaire...', lang === 'en' ? 'Write a comment...' : (lang === 'ar' ? 'اكتب تعليقا...' : 'Ecrire un commentaire...')],
    ['VidÃƒÂ©o', 'Video'], ['VidÃ©o', 'Video'], ['vidÃƒÂ©os', 'videos'], ['vidÃ©os', 'videos'],
    ['RÃƒÂ©pondre', labels.reply], ['RÃ©pondre', labels.reply],
    ['RejetÃƒÂ©s', lang === 'en' ? 'Rejected' : 'Rejetes'], ['RejetÃ©s', lang === 'en' ? 'Rejected' : 'Rejetes'],
    ['SchÃƒÂ©mas dynamiques du forum', lang === 'en' ? 'Dynamic forum charts' : 'Schemas dynamiques du forum'], ['SchÃ©mas dynamiques du forum', lang === 'en' ? 'Dynamic forum charts' : 'Schemas dynamiques du forum']
  ]);
  function cleanText(value) {
    if (!value || !/[ÃÂâð�]/.test(value)) return value;
    let out = value;
    exact.forEach((to, from) => { out = out.split(from).join(to); });
    out = out
      .replace(/Forum\s*&\s*[ÃÂ][^\n]{0,24}changes/g, labels.forumTitle)
      .replace(/Publiez,[^\n]{0,180}sociaux\./g, labels.forumSub)
      .replace(/Posts\s+enregistr[^\s<]*/g, labels.saved)
      .replace(/Plus\s+r[^\s<]*cents/g, labels.recent)
      .replace(/Aucun\s+r[^\s<]*sultat/g, labels.noResults)
      .replace(/Aucun\s+post\s+trouv[^\s<]*\./g, labels.noPost)
      .replace(/vid[ÃÂ][^\s<]*os/g, 'videos')
      .replace(/Vid[ÃÂ][^\s<]*o/g, 'Video')
      .replace(/R[ÃÂ][^\s<]*pondre/g, labels.reply)
      .replace(/Ã[^\s<]{2,}/g, '')
      .replace(/Â[^\s<]{1,}/g, '')
      .replace(/â[^\s<]{1,}/g, '')
      .replace(/ð[^\s<]{1,}/g, '')
      .replace(/\s{2,}/g, ' ')
      .trim();
    return out || value;
  }
  function walk(root) {
    if (!root) return;
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
      acceptNode(node) {
        const parent = node.parentElement;
        if (!parent || /^(SCRIPT|STYLE|TEXTAREA|INPUT|CODE|PRE)$/i.test(parent.tagName)) return NodeFilter.FILTER_REJECT;
        return /[ÃÂâð�]/.test(node.nodeValue || '') ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
      }
    });
    const nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach(node => { node.nodeValue = cleanText(node.nodeValue); });
    document.querySelectorAll('[placeholder],[title],[aria-label],input[value],button[value]').forEach(el => {
      ['placeholder','title','aria-label','value'].forEach(attr => {
        if (el.hasAttribute(attr)) el.setAttribute(attr, cleanText(el.getAttribute(attr)));
      });
    });
  }
  document.addEventListener('DOMContentLoaded', () => {
    walk(document.body);
    setTimeout(() => walk(document.body), 300);
    setTimeout(() => walk(document.body), 1200);
  });
  window.GoServiceCleanText = cleanText;
  window.GoServiceCleanDom = walk;
})();