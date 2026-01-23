<?php
/**
 * Mermaid 配置管理单元测试
 * 
 * 测试 functions.php 中的 Mermaid 配置管理函数
 */

class Test_Mermaid_Config extends WP_UnitTestCase {
	
	/**
	 * 测试前清理配置
	 */
	public function setUp() {
		parent::setUp();
		
		// 清理所有 Mermaid 配置
		$options = [
			'argon_enable_mermaid',
			'argon_mermaid_cdn_source',
			'argon_mermaid_cdn_custom_url',
			'argon_mermaid_theme',
			'argon_mermaid_use_local',
			'argon_mermaid_debug_mode'
		];
		
		foreach ($options as $option) {
			delete_option($option);
		}
	}
	
	/**
	 * 测试后清理配置
	 */
	public function tearDown() {
		$this->setUp(); // 重用清理逻辑
		parent::tearDown();
	}
	
	/**
	 * 测试获取配置选项（使用简短名称）
	 */
	public function test_get_mermaid_option_with_short_name() {
		update_option('argon_enable_mermaid', true);
		$this->assertTrue(argon_get_mermaid_option('enabled'));
	}
	
	/**
	 * 测试获取配置选项（使用完整名称）
	 */
	public function test_get_mermaid_option_with_full_name() {
		update_option('argon_enable_mermaid', true);
		$this->assertTrue(argon_get_mermaid_option('argon_enable_mermaid'));
	}
	
	/**
	 * 测试获取不存在的配置选项（返回默认值）
	 */
	public function test_get_mermaid_option_with_default() {
		$this->assertEquals('default_value', argon_get_mermaid_option('non_existent', 'default_value'));
	}
	
	/**
	 * 测试保存配置选项（使用简短名称）
	 */
	public function test_update_mermaid_option_with_short_name() {
		$result = argon_update_mermaid_option('enabled', true);
		$this->assertTrue($result);
		$this->assertTrue(get_option('argon_enable_mermaid'));
	}
	
	/**
	 * 测试保存配置选项（使用完整名称）
	 */
	public function test_update_mermaid_option_with_full_name() {
		$result = argon_update_mermaid_option('argon_enable_mermaid', true);
		$this->assertTrue($result);
		$this->assertTrue(get_option('argon_enable_mermaid'));
	}
	
	/**
	 * 测试验证有效的 CDN URL
	 */
	public function test_validate_cdn_url_with_valid_url() {
		$url = 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js';
		$this->assertTrue(argon_validate_mermaid_cdn_url($url));
	}
	
	/**
	 * 测试验证无效的 CDN URL（不是 URL）
	 */
	public function test_validate_cdn_url_with_invalid_url() {
		$url = 'not-a-valid-url';
		$this->assertFalse(argon_validate_mermaid_cdn_url($url));
	}
	
	/**
	 * 测试验证无效的 CDN URL（不以 .js 结尾）
	 */
	public function test_validate_cdn_url_without_js_extension() {
		$url = 'https://example.com/mermaid.css';
		$this->assertFalse(argon_validate_mermaid_cdn_url($url));
	}
	
	/**
	 * 测试验证空 CDN URL
	 */
	public function test_validate_cdn_url_with_empty_string() {
		$this->assertFalse(argon_validate_mermaid_cdn_url(''));
		$this->assertFalse(argon_validate_mermaid_cdn_url('   '));
	}
	
	/**
	 * 测试验证无效协议的 CDN URL
	 */
	public function test_validate_cdn_url_with_invalid_protocol() {
		$url = 'ftp://example.com/mermaid.min.js';
		$this->assertFalse(argon_validate_mermaid_cdn_url($url));
	}
	
	/**
	 * 测试获取 Mermaid 主题（配置为 default）
	 */
	public function test_get_mermaid_theme_with_default() {
		update_option('argon_mermaid_theme', 'default');
		$this->assertEquals('default', argon_get_mermaid_theme());
	}
	
	/**
	 * 测试获取 Mermaid 主题（配置为 dark）
	 */
	public function test_get_mermaid_theme_with_dark() {
		update_option('argon_mermaid_theme', 'dark');
		$this->assertEquals('dark', argon_get_mermaid_theme());
	}
	
	/**
	 * 测试获取 Mermaid 主题（配置为 auto）
	 */
	public function test_get_mermaid_theme_with_auto() {
		update_option('argon_mermaid_theme', 'auto');
		// auto 模式在 PHP 端返回 default，实际切换在 JS 端
		$this->assertEquals('default', argon_get_mermaid_theme());
	}
	
	/**
	 * 测试获取默认配置
	 */
	public function test_get_mermaid_default_config() {
		$defaults = argon_get_mermaid_default_config();
		
		$this->assertIsArray($defaults);
		$this->assertArrayHasKey('enabled', $defaults);
		$this->assertArrayHasKey('cdn_source', $defaults);
		$this->assertArrayHasKey('theme', $defaults);
		$this->assertFalse($defaults['enabled']);
		$this->assertEquals('jsdelivr', $defaults['cdn_source']);
		$this->assertEquals('auto', $defaults['theme']);
	}
	
	/**
	 * 测试验证有效的配置
	 */
	public function test_validate_mermaid_settings_with_valid_settings() {
		$settings = [
			'enabled' => true,
			'cdn_source' => 'jsdelivr',
			'theme' => 'default',
			'use_local' => false,
			'debug_mode' => false
		];
		
		$errors = argon_validate_mermaid_settings($settings);
		$this->assertEmpty($errors);
	}
	
	/**
	 * 测试验证无效的 CDN 来源
	 */
	public function test_validate_mermaid_settings_with_invalid_cdn_source() {
		$settings = [
			'cdn_source' => 'invalid_source'
		];
		
		$errors = argon_validate_mermaid_settings($settings);
		$this->assertNotEmpty($errors);
	}
	
	/**
	 * 测试验证自定义 CDN 但 URL 为空
	 */
	public function test_validate_mermaid_settings_with_custom_cdn_empty_url() {
		$settings = [
			'cdn_source' => 'custom',
			'custom_cdn_url' => ''
		];
		
		$errors = argon_validate_mermaid_settings($settings);
		$this->assertNotEmpty($errors);
	}
	
	/**
	 * 测试验证自定义 CDN 但 URL 无效
	 */
	public function test_validate_mermaid_settings_with_custom_cdn_invalid_url() {
		$settings = [
			'cdn_source' => 'custom',
			'custom_cdn_url' => 'not-a-url'
		];
		
		$errors = argon_validate_mermaid_settings($settings);
		$this->assertNotEmpty($errors);
	}
	
	/**
	 * 测试验证无效的主题名称
	 */
	public function test_validate_mermaid_settings_with_invalid_theme() {
		$settings = [
			'theme' => 'invalid_theme'
		];
		
		$errors = argon_validate_mermaid_settings($settings);
		$this->assertNotEmpty($errors);
	}
	
	/**
	 * 测试初始化默认配置
	 */
	public function test_init_mermaid_config() {
		argon_init_mermaid_config();
		
		$this->assertFalse(get_option('argon_enable_mermaid'));
		$this->assertEquals('jsdelivr', get_option('argon_mermaid_cdn_source'));
		$this->assertEquals('auto', get_option('argon_mermaid_theme'));
	}
	
	/**
	 * 测试初始化不覆盖已有配置
	 */
	public function test_init_mermaid_config_does_not_override() {
		update_option('argon_enable_mermaid', true);
		argon_init_mermaid_config();
		
		// 已有配置不应该被覆盖
		$this->assertTrue(get_option('argon_enable_mermaid'));
	}
	
	/**
	 * 测试获取所有配置选项
	 */
	public function test_get_all_mermaid_options() {
		update_option('argon_enable_mermaid', true);
		update_option('argon_mermaid_cdn_source', 'unpkg');
		
		$options = argon_get_all_mermaid_options();
		
		$this->assertIsArray($options);
		$this->assertTrue($options['enabled']);
		$this->assertEquals('unpkg', $options['cdn_source']);
	}
	
	/**
	 * 测试批量更新配置（成功）
	 */
	public function test_update_mermaid_settings_success() {
		$settings = [
			'enabled' => true,
			'cdn_source' => 'jsdelivr',
			'theme' => 'dark'
		];
		
		$result = argon_update_mermaid_settings($settings);
		
		$this->assertTrue($result['success']);
		$this->assertEmpty($result['errors']);
		$this->assertTrue(get_option('argon_enable_mermaid'));
		$this->assertEquals('dark', get_option('argon_mermaid_theme'));
	}
	
	/**
	 * 测试批量更新配置（失败）
	 */
	public function test_update_mermaid_settings_failure() {
		$settings = [
			'cdn_source' => 'invalid_source'
		];
		
		$result = argon_update_mermaid_settings($settings);
		
		$this->assertFalse($result['success']);
		$this->assertNotEmpty($result['errors']);
	}
}
