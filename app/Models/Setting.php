<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Attributes\Fillable;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Support\Facades\Cache;

	#[Fillable([
			'key',
			'value',
	])]
	class Setting extends Model
	{
		public function casts(): array
		{
			return [
					'value' => 'string',
			];
		}

		/**
		 * Возвращает значение настройки по ключу.
		 */
		public static function get(string $key, ?string $default = null): ?string
		{
			$settings = Cache::rememberForever('settings', static fn () => static::query()
					->pluck('value', 'key')
					->all());

			return $settings[$key] ?? $default;
		}

		/**
		 * Сохраняет значение настройки и сбрасывает кеш.
		 */
		public static function set(string $key, ?string $value): void
		{
			static::updateOrCreate(['key' => $key], ['value' => $value]);

			Cache::forget('settings');
		}

		public static function enabled(string $key, bool $default = false): bool
		{
			$value = static::get($key, $default ? '1' : '0');

			return $value === '1';
		}
	}
