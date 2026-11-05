const assert = require('node:assert/strict');
const path = require('node:path');
const { chromium } = require('playwright');
const outputDir = path.join(require('node:os').tmpdir(), 'argon-settings-qa');
require('node:fs').mkdirSync(outputDir, {recursive:true});
const loginCss = path.join(__dirname, '..', 'login.css');
async function addLoginStyles(page) {
 await page.addStyleTag({path:path.join(__dirname, '..', 'assets/css/aurora-background.css')});
 await page.addStyleTag({path:loginCss});
 await page.addStyleTag({content:':root { --themecolor: #fa7298; --argon-on-accent: #000; }'});
}
function markup(mode) {
 const fields = mode === 'login' ? '<p><label for="user_login">用户名或电子邮箱地址</label><input class="input" id="user_login" type="text"></p><p><label for="user_pass">密码</label><span class="wp-pwd"><input class="input password-input" id="user_pass" type="password"><button type="button" class="button wp-hide-pw" aria-label="显示密码">◉</button></span></p><p class="forgetmenot"><input id="rememberme" type="checkbox"><label for="rememberme">记住我</label></p>' : '<p class="message">请输入您的用户名或电子邮箱地址。您会收到一封包含重置密码说明的邮件。</p><p><label for="user_login">用户名或电子邮箱地址</label><input class="input" id="user_login" type="email"></p>';
 return `<!doctype html><html lang="zh-CN"><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{margin:0}h1{font-size:24px}p{margin:0}#login{margin:auto;width:320px}.wp-pwd{display:block}button,input,select{font:inherit}.login h1 a{display:block;background-image:url(x);height:84px;width:84px;text-indent:-9999px}</style></head><body class="login"><div id="login"><h1><a href="/">编程小记 · Ave Mujika</a></h1>${mode === 'login' ? '<div id="login_error">用户名或密码不正确，请重试。</div>' : ''}<form>${fields}<p class="submit"><input type="submit" class="button button-primary" value="${mode === 'login' ? '登录' : '获取新密码'}"></p></form><p id="nav"><a href="/reset">忘记密码？</a></p><p id="backtoblog"><a href="/">返回编程小记</a></p><div class="privacy-policy-page-link"><a class="privacy-policy-link" href="/privacy">隐私政策</a></div></div><div class="language-switcher"><form><select aria-label="语言"><option>简体中文</option></select><input type="submit" class="button" value="更改"></form></div></body></html>`;
}
(async () => {
 const browser = await chromium.launch({headless:true});
 try {
  const page = await browser.newPage();
  for (const mode of ['login', 'recovery']) for (const scheme of ['light','dark']) for (const width of [320,390,1280]) {
   await page.setViewportSize({width, height:width === 1280 ? 800 : 640});
   await page.emulateMedia({colorScheme:scheme, reducedMotion:'reduce'});
   await page.setContent(markup(mode)); await addLoginStyles(page);
   assert.ok(await page.locator('#login h1 a').isVisible());
   const button = await page.locator('.button-primary').evaluate(node => {
    const css = getComputedStyle(node);
    return [css.backgroundColor, css.borderTopLeftRadius, css.fontWeight];
   });
   assert.deepEqual(button, ['rgb(250, 114, 152)', width <= 768 ? '12px' : '28px', '500']);
   assert.equal(await page.locator('#login h1 a').evaluate(node => getComputedStyle(node).textIndent), '0px');
   assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
   const input = page.locator('#user_login');
   assert.ok(await input.evaluate(node => parseFloat(getComputedStyle(node).fontSize) >= 16), 'Prevent mobile input zoom');
   await input.fill('user@example.com'); await input.focus();
   assert.ok(await input.evaluate(node => parseFloat(getComputedStyle(node).outlineWidth) >= 2));
   const form = await page.locator('#login > form').boundingBox();
   const language = await page.locator('.language-switcher').boundingBox();
   assert.ok(language.y >= form.y + form.height, 'Language controls must not overlay long forms');
   assert.ok(await page.locator('.button-primary').evaluate(node => node.getBoundingClientRect().height >= 44));
  }
  await page.setViewportSize({width:390,height:844}); await page.emulateMedia({colorScheme:'dark'});await page.setContent(markup('login'));await addLoginStyles(page);
  await page.screenshot({path:path.join(outputDir, 'login-mobile.png'),fullPage:true});
  console.log('[PASS] Login and recovery layouts: light/dark, 320/390/1280 px, visible branding, focus, touch targets and language switcher');
 } finally {await browser.close();}
})().catch(error => {console.error(error);process.exitCode=1});
