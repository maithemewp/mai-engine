<?php
namespace Mai\Cache\Tests\Support;

use Mai\Cache\Store;

/**
 * In-memory Store double. read() returns false on miss, mirroring transients.
 */
final class ArrayStore implements Store {
	public array $data      = [];
	public bool  $available = true;

	public function read( string $key ): mixed {
		return array_key_exists( $key, $this->data ) ? $this->data[ $key ] : false;
	}

	public function write( string $key, mixed $value, int $expire ): bool {
		$this->data[ $key ] = $value;
		return true;
	}

	public function remove( string $key ): bool {
		unset( $this->data[ $key ] );
		return true;
	}

	public function available(): bool {
		return $this->available;
	}
}
