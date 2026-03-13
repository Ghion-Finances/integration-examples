<?php

declare(strict_types=1);

namespace Ghion\HostedCheckout\Model;

/**
 * Simple product model for demonstration
 */
class Product
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $description,
        public readonly int $priceInCents,
        public readonly string $currency = 'ETB',
        public readonly ?string $imageUrl = null
    ) {
    }

    public function getPriceFormatted(): string
    {
        $major = $this->priceInCents / 100;
        return number_format($major, 2) . ' ' . $this->currency;
    }

    /**
     * Get sample products for demo
     *
     * @return array<Product>
     */
    public static function getSampleProducts(): array
    {
        return [
            new self(
                'prod_001',
                'Premium Coffee Beans',
                'Ethiopian Yirgacheffe - 1kg bag of premium arabica coffee beans',
                89900,
                'ETB',
                'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=400'
            ),
            new self(
                'prod_002',
                'Handwoven Basket',
                'Traditional Ethiopian handwoven basket - Large size',
                45000,
                'ETB',
                'https://images.unsplash.com/photo-1523293182086-7651a899d37f?w=400'
            ),
            new self(
                'prod_004',
                'Cotton Scarf',
                'Hand-spun cotton scarf with traditional patterns',
                25000,
                'ETB',
                'https://images.unsplash.com/photo-1601924994987-69e26d50dc26?w=400'
            ),
        ];
    }

    /**
     * Find product by ID
     */
    public static function findById(string $id): ?self
    {
        foreach (self::getSampleProducts() as $product) {
            if ($product->id === $id) {
                return $product;
            }
        }
        return null;
    }
}
