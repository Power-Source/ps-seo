<?php

namespace PSource\SEO\Integration;

final class MarketPress {

	public static function is_product( int $post_id ): bool {
		return class_exists( '\MP_Product' )
			&& get_post_type( $post_id ) === \MP_Product::get_post_type();
	}

	public static function schema( int $post_id ): ?array {
		if ( ! self::is_product( $post_id ) ) {
			return null;
		}

		$product    = new \MP_Product( $post_id );
		$variations = $product->get_variations();
		$url        = (string) get_permalink( $post_id );
		$schema     = self::product_data( $product, $url . '#product', $url );

		if ( $variations ) {
			$schema['@type']      = 'ProductGroup';
			$schema['hasVariant'] = array();

			foreach ( $variations as $variation ) {
				if ( ! $variation instanceof \MP_Product ) {
					continue;
				}

				$variation_schema                = self::product_data( $variation, $url . '#product-' . $variation->ID, $url );
				$variation_schema['isVariantOf'] = array( '@id' => $url . '#product' );
				$schema['hasVariant'][]          = $variation_schema;
			}
		}

		return apply_filters( 'ps_seo_marketpress_product_schema', $schema, $product );
	}

	/**
	 * @return array{views:int,sales:int,paid_sales:int,revenue:float,downloads:int}|null
	 */
	public static function performance_metrics( int $post_id, string $from, string $to ): ?array {
		if ( ! self::is_product( $post_id ) || ! function_exists( 'ps_stats_provider' ) ) {
			return null;
		}

		$provider = ps_stats_provider();
		if (
			! is_object( $provider )
			|| ! method_exists( $provider, 'availability' )
			|| ! method_exists( $provider, 'product_metrics' )
			|| ! $provider->availability()
		) {
			return null;
		}

		$metrics = $provider->product_metrics( $post_id, $from, $to );
		if ( ! is_array( $metrics ) ) {
			return null;
		}

		return array(
			'views'       => max( 0, (int) ( $metrics['views'] ?? 0 ) ),
			'sales'       => max( 0, (int) ( $metrics['sales'] ?? 0 ) ),
			'paid_sales'  => max( 0, (int) ( $metrics['paid_sales'] ?? 0 ) ),
			'revenue'     => max( 0, (float) ( $metrics['revenue'] ?? 0 ) ),
			'downloads'   => max( 0, (int) ( $metrics['downloads'] ?? 0 ) ),
		);
	}

	public static function currency(): string {
		$currency = 'EUR';

		if ( function_exists( 'mp_get_setting' ) ) {
			$cart      = function_exists( 'mp_cart' ) ? mp_cart() : null;
			$is_global = is_multisite() && is_object( $cart ) && ! empty( $cart->is_global );

			$currency = $is_global && function_exists( 'mp_get_network_setting' )
				? mp_get_network_setting( 'global_currency', 'EUR' )
				: mp_get_setting( 'currency', 'EUR' );
		}

		return strtoupper( sanitize_text_field( (string) $currency ) );
	}

	private static function product_data( \MP_Product $product, string $id, string $url ): array {
		$product_id  = (int) $product->ID;
		$description = trim( wp_strip_all_tags( (string) $product->excerpt() ) );
		$schema      = array(
			'@type'       => 'Product',
			'@id'         => $id,
			'name'        => wp_strip_all_tags( (string) $product->title( false ) ),
			'url'         => $url,
			'description' => $description,
		);

		$sku = trim( (string) $product->get_meta( 'sku', '' ) );
		if ( '' !== $sku ) {
			$schema['sku'] = $sku;
		}

		$image_id = get_post_thumbnail_id( $product_id );
		$image    = $image_id ? wp_get_attachment_image_src( $image_id, 'full' ) : false;
		if ( $image ) {
			$schema['image'] = array(
				'@type'  => 'ImageObject',
				'url'    => $image[0],
				'width'  => $image[1],
				'height' => $image[2],
			);
		}

		$offer = self::offer( $product, $url );
		if ( $offer ) {
			$schema['offers'] = $offer;
		}

		return array_filter(
			$schema,
			static fn( mixed $value ): bool => null !== $value && '' !== $value && array() !== $value
		);
	}

	private static function offer( \MP_Product $product, string $url ): ?array {
		$prices = $product->get_price();
		if ( ! is_array( $prices ) ) {
			return null;
		}

		$sale  = isset( $prices['sale'] ) && is_array( $prices['sale'] ) ? $prices['sale'] : array();
		$price = self::numeric_price( $sale['amount'] ?? null );

		if ( null === $price ) {
			$price = self::numeric_price( $prices['lowest'] ?? null );
		}
		if ( null === $price ) {
			$price = self::numeric_price( $prices['regular'] ?? null );
		}
		if ( null === $price ) {
			return null;
		}

		$offer = array(
			'@type'         => 'Offer',
			'url'           => $url,
			'price'         => number_format( $price, 2, '.', '' ),
			'priceCurrency' => self::currency(),
			'availability'  => $product->in_stock()
				? 'https://schema.org/InStock'
				: 'https://schema.org/OutOfStock',
			'itemCondition' => 'https://schema.org/NewCondition',
		);

		if ( ! empty( $sale['end_date'] ) ) {
			$timestamp = strtotime( (string) $sale['end_date'] );
			if ( false !== $timestamp ) {
				$offer['priceValidUntil'] = gmdate( 'Y-m-d', $timestamp );
			}
		}

		return $offer;
	}

	private static function numeric_price( mixed $value ): ?float {
		return is_numeric( $value ) ? (float) $value : null;
	}

}
