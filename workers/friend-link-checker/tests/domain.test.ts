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
    expect(buildCandidates('sample-blog.dev')).toEqual([
      'https://sample-blog.dev/',
      'https://www.sample-blog.dev/',
      'https://blog.sample-blog.dev/',
      'http://sample-blog.dev/',
      'http://www.sample-blog.dev/',
      'http://blog.sample-blog.dev/'
    ]);
  });

  it('keeps an entered blog host first before the root and www variants', () => {
    expect(buildCandidates('http://blog.sample-blog.dev/posts')).toEqual([
      'http://blog.sample-blog.dev/',
      'http://sample-blog.dev/',
      'http://www.sample-blog.dev/',
      'https://blog.sample-blog.dev/',
      'https://sample-blog.dev/',
      'https://www.sample-blog.dev/'
    ]);
  });

  it('treats root, www and blog as the same permitted site family', () => {
    expect(isSameCandidateSite('https://sample-blog.dev', 'https://www.sample-blog.dev/about')).toBe(true);
    expect(isSameCandidateSite('https://blog.sample-blog.dev', 'https://attacker.example')).toBe(false);
  });

  it('does not cross a private Public Suffix boundary or expand arbitrary subdomains', () => {
    expect(buildCandidates('blog.github.io')).not.toContain('https://github.io/');
    expect(isSameCandidateSite('https://blog.github.io', 'https://github.io')).toBe(false);
    expect(buildCandidates('notes.sample-blog.dev')).toEqual([
      'https://notes.sample-blog.dev/',
      'http://notes.sample-blog.dev/'
    ]);
  });

  it('normalizes www only when comparing backlink hosts', () => {
    expect(normalizeComparableHostname('https://www.sample-blog.dev/path')).toBe('sample-blog.dev');
    expect(normalizeComparableHostname('https://blog.sample-blog.dev')).toBe('blog.sample-blog.dev');
  });

  it('rejects local, private and non-HTTP targets', () => {
    for (const value of [
      'http://127.0.0.1',
      'http://192.168.1.2',
      'http://169.254.169.254/latest/meta-data',
      'http://[::1]/',
      'http://router.local',
      'https://blog.example.com',
      'ftp://sample-blog.dev'
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
