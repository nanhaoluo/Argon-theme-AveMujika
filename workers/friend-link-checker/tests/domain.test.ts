import { describe, expect, it } from 'vitest';
import {
  assertSafePublicUrl,
  buildCandidates,
  isSameCandidateSite,
  normalizeComparableHostname,
  splitTitle
} from '../src/domain';

describe('friend-link target normalization', () => {
  it('expands a bare domain to root, www and blog with HTTP fallback', () => {
    expect(buildCandidates('cartol.top')).toEqual([
      'https://cartol.top/',
      'https://www.cartol.top/',
      'https://blog.cartol.top/',
      'http://cartol.top/',
      'http://www.cartol.top/',
      'http://blog.cartol.top/'
    ]);
  });

  it('keeps an entered blog host first before the root and www variants', () => {
    expect(buildCandidates('http://blog.cartol.top/posts')).toEqual([
      'http://blog.cartol.top/',
      'http://cartol.top/',
      'http://www.cartol.top/',
      'https://blog.cartol.top/',
      'https://cartol.top/',
      'https://www.cartol.top/'
    ]);
  });

  it('treats root, www and blog as the same permitted site family', () => {
    expect(isSameCandidateSite('https://cartol.top', 'https://www.cartol.top/about')).toBe(true);
    expect(isSameCandidateSite('https://blog.cartol.top', 'https://attacker.example')).toBe(false);
  });

  it('does not cross a private Public Suffix boundary or expand arbitrary subdomains', () => {
    expect(buildCandidates('blog.github.io')).not.toContain('https://github.io/');
    expect(isSameCandidateSite('https://blog.github.io', 'https://github.io')).toBe(false);
    expect(buildCandidates('notes.cartol.top')).toEqual([
      'https://notes.cartol.top/',
      'http://notes.cartol.top/'
    ]);
  });

  it('normalizes www only when comparing backlink hosts', () => {
    expect(normalizeComparableHostname('https://www.cartol.top/path')).toBe('cartol.top');
    expect(normalizeComparableHostname('https://blog.cartol.top')).toBe('blog.cartol.top');
  });

  it('rejects local, private and non-HTTP targets', () => {
    for (const value of [
      'http://127.0.0.1',
      'http://192.168.1.2',
      'http://169.254.169.254/latest/meta-data',
      'http://[::1]/',
      'http://router.local',
      'https://blog.example.com',
      'ftp://cartol.top'
    ]) {
      expect(() => assertSafePublicUrl(value), value).toThrow();
    }
  });
});

describe('site title recognition', () => {
  it('recognizes the primary and subtitle separated by a long dash', () => {
    expect(splitTitle('编程小记 – 其实我也不知道建这个站拿来干嘛=-=', '')).toEqual({
      primary: '编程小记',
      subtitle: '其实我也不知道建这个站拿来干嘛=-='
    });
  });

  it('uses an explicit site name as the primary title', () => {
    expect(splitTitle('文章列表 | 编程小记', '编程小记')).toEqual({
      primary: '编程小记',
      subtitle: '文章列表'
    });
  });
});
