function renderSpotlight(products){const list=products.slice(0,3);if(!list.length)return;const stage=document.getElementById('spotlight-image'),thumbs=document.getElementById('spotlight-thumbs');function image(p){try{const url=new URL(p.pic,location.href);if(url.protocol!=='https:'&&url.origin!==location.origin)return null;const img=document.createElement('img');img.src=url.href;img.alt=p.name||'Product';img.addEventListener('error',()=>img.replaceWith(document.createTextNode('NextGen')),{once:true});return img;}catch{return null;}}function select(p,i){stage.replaceChildren(image(p)||document.createTextNode('NextGen'));document.getElementById('spotlight-title').textContent=p.name||'Product';const amount=Number(p.price);document.getElementById('spotlight-price').textContent=Number.isFinite(amount)?'K '+amount.toFixed(2):'Price unavailable';[...thumbs.children].forEach((b,n)=>b.setAttribute('aria-pressed',String(i===n)));}thumbs.replaceChildren();list.forEach((p,i)=>{const b=document.createElement('button');b.type='button';b.setAttribute('aria-label','Show '+p.name);b.append(image(p)||document.createTextNode(String(i+1)));b.addEventListener('click',()=>select(p,i));thumbs.append(b);});select(list[0],0);}
const $ = (id) => document.getElementById(id);
if (document.body.dataset.signedIn !== 'true') {
  fetch('/b2c-preview/api.php?action=list', {credentials: 'same-origin', cache: 'no-store'})
    .then(async response => { if (!response.ok) throw new Error('The retail catalogue will be available soon.'); return response.json(); })
    .then(data => {
      renderSpotlight(data.products || []);
      for (const product of data.products || []) {
        const card = document.createElement('article'); card.className = 'card';
        const link = document.createElement('a'); link.href = '/register'; link.className = 'catalogue-product';
        const picture = document.createElement('div'); picture.className = 'picture'; picture.textContent = 'NEXTGEN';
        try { const url = new URL(product.pic,location.href); if ((url.protocol === 'https:' || url.origin === location.origin) && !url.username && !url.password) { const img = document.createElement('img'); img.src = url.href; img.alt = product.name || 'Product'; img.loading = 'lazy'; img.referrerPolicy = 'no-referrer'; img.addEventListener('error',()=>{picture.textContent='NEXTGEN';},{once:true}); picture.replaceChildren(img); } } catch {}
        const title = document.createElement('h2'); title.textContent = product.name || 'Product';
        const price = document.createElement('p'); price.className = 'product-price'; price.textContent = 'K '+Number(product.price).toFixed(2);
        const note = document.createElement('p'); note.textContent = 'Create an account or sign in for details'; note.className = 'muted';
        link.append(picture, title); card.append(link, price, note); $('products').append(card);
      }
      $('status').textContent = data.products?.length ? `${data.products.length} products · Prices shown in Papua New Guinean kina` : 'No products are listed yet. Contact NextGen for availability.';
      if (!data.products?.length) $('status').className = 'empty-state';
    }).catch(error => { $('status').textContent = error.message; $('status').className = 'empty-state'; });
} else {
let page = 1;
let pages = 1;
let requestNumber = 0;
const basket = new Map();
$('keyword').value = new URLSearchParams(location.search).get('q') || '';
const number = new Intl.NumberFormat('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
function node(tag, text, className) {
  const result = document.createElement(tag);
  if (text !== undefined) result.textContent = text;
  if (className) result.className = className;
  return result;
}
function picture(product) {
  const placeholder = node('div', 'NextGen', 'picture');
  try {
    const url = new URL(product.pic,location.href);
    if ((url.protocol !== 'https:' && url.origin !== location.origin) || url.username || url.password) return placeholder;
    const img = node('img'); img.src = url.href; img.alt = product.name || 'Product';
    img.loading = 'lazy'; img.referrerPolicy = 'no-referrer';
    img.addEventListener('error', () => img.replaceWith(node('span', 'NextGen')), { once: true });
    placeholder.replaceChildren(img);
  } catch {}
  return placeholder;
}
function price(product) {
  const value = Number(product.price);
  return Number.isFinite(value) && value >= 0 ? `K ${number.format(value)}` : 'Price unavailable';
}
async function api(parameters) {
  const response = await fetch(`/b2c-preview/api.php?${new URLSearchParams(parameters)}`, { credentials: 'same-origin', cache: 'no-store' });
  const data = await response.json();
  if (!response.ok) throw new Error(data.error || 'Catalogue unavailable');
  return data;
}
function add(product) {
  const key = String(product.id);
  const existing = basket.get(key);
  basket.set(key, { product, quantity: Math.min(99, (existing?.quantity || 0) + 1) });
  $('cart-count').textContent = [...basket.values()].reduce((sum, entry) => sum + entry.quantity, 0);
  $('status').textContent = `${product.name} added to the test basket. No order submitted.`;
}
function productCard(product) {
  const card = node('article', undefined, 'card');
  const details = node('button', undefined, 'product-link');
  details.append(picture(product), node('h2', product.name || 'Product'));
  details.addEventListener('click', () => showDetail(product.id));
  card.append(details, node('p', price(product)));
  const button = node('button', 'Add to test basket'); button.type = 'button';
  button.addEventListener('click', () => add(product)); card.append(button);
  return card;
}
async function load() {
  const current = ++requestNumber;
  $('status').textContent = 'Loading catalogue…';
  $('previous').disabled = true; $('next').disabled = true;
  try {
    const data = await api({ action: 'list', page, sort: $('sort').value, keyword: $('keyword').value.trim() });
    if (current !== requestNumber) return;
    pages = data.pages;
    renderSpotlight(data.products);
    $('products').replaceChildren(...data.products.map(productCard));
    $('status').textContent = data.products.length ? `${data.products.length} products on this page` : 'No retail products found. The new B2C catalogue may be empty.';
    $('page').textContent = `Page ${page} of ${pages}`;
    $('previous').disabled = page <= 1; $('next').disabled = page >= pages;
  } catch (error) {
    if (current !== requestNumber) return;
    $('products').replaceChildren(); $('status').textContent = error.message;
  }
}
let detailRequest = 0;
async function showDetail(id) {
  const current = ++detailRequest;
  const dialog = $('detail'); $('detail-content').replaceChildren(node('p', 'Loading product…'));
  if (!dialog.open) dialog.showModal();
  try {
    const { product } = await api({ action: 'detail', id });
    if (current !== detailRequest || !dialog.open) return;
    const button = node('button', 'Add to test basket'); button.type = 'button'; button.addEventListener('click', () => add(product));
    $('detail-content').replaceChildren(picture(product), node('h2', product.name), node('p', product.description || 'Product details coming soon.'), node('p', price(product)), button);
  } catch (error) { if (current === detailRequest) $('detail-content').replaceChildren(node('p', error.message)); }
}
function showBasket() {
  const rows = [...basket.values()].map(({ product, quantity }) => {
    const row = node('div', undefined, 'basket-row');
    row.append(node('p', `${product.name} × ${quantity}`));
    const remove = node('button', 'Remove'); remove.type = 'button';
    remove.addEventListener('click', () => { basket.delete(String(product.id)); $('cart-count').textContent = [...basket.values()].reduce((s, e) => s + e.quantity, 0); showBasket(); });
    row.append(remove); return row;
  });
  $('basket-items').replaceChildren(...(rows.length ? rows : [node('p', 'Your test basket is empty.')]));
  if (!$('basket').open) $('basket').showModal();
}
$('search').addEventListener('submit', (event) => { event.preventDefault(); page = 1; load(); });
$('previous').addEventListener('click', () => { if (page > 1) { page--; load(); } });
$('next').addEventListener('click', () => { if (page < pages) { page++; load(); } });
$('cart-open').addEventListener('click', showBasket);
$('clear-basket').addEventListener('click', () => { basket.clear(); $('cart-count').textContent = '0'; showBasket(); });
document.querySelectorAll('dialog .close').forEach((button) => button.addEventListener('click', () => button.closest('dialog').close()));
load();
}
