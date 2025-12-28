<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Warehouse;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Clear existing data
        $this->command->info('Clearing existing data...');
        
        // Seed Users
        $this->seedUsers();
        
        // Seed Categories and Products
        $this->seedCategories();
        $this->seedProducts();
        
        // Seed Customers
        $this->seedCustomers();
        
        // Seed Warehouses
        $this->seedWarehouses();
        
        // Seed Inventory
        $this->seedInventory();

        // Seed Orders
        $this->seedOrders();

        // Seed Order Items
        $this->seedOrderItems();
        
        $this->command->info('Database seeding completed successfully!');
        
    }

    private function seedUsers(): void
    {
        $this->command->info('Seeding users...');

        $users = [
            [
                'username' => 'LEYS-1001',
                'email' => 'david.kariuki@leysco.co.ke',
                'password' => 'SecurePass123!',
                'first_name' => 'David',
                'last_name' => 'Kariuki',
                'role' => 'Sales Manager',
                'permissions' => ['view_all_sales', 'create_sales', 'approve_sales', 'manage_inventory'],
                'status' => 'active',
            ],
            [
                'username' => 'LEYS-1002',
                'email' => 'jane.njoki@leysco.co.ke',
                'password' => 'SecurePass456!',
                'first_name' => 'Jane',
                'last_name' => 'Njoki',
                'role' => 'Sales Representative',
                'permissions' => ['view_own_sales', 'create_sales', 'view_inventory'],
                'status' => 'active',
            ],
        ];

        foreach ($users as $userData) {
            User::create([
                'username' => $userData['username'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'role' => $userData['role'],
                'permissions' => $userData['permissions'],
                'status' => $userData['status'],
            ]);
        }

        $this->command->info('Users seeded: ' . count($users));
    }

    private function seedCategories(): void
    {
        $this->command->info('Seeding categories...');

        $categories = [
            ['name' => 'Engine Oils', 'slug' => 'engine-oils'],
            ['name' => 'Transmission Fluids', 'slug' => 'transmission-fluids'],
            ['name' => 'Brake Fluids', 'slug' => 'brake-fluids'],
            ['name' => 'Coolants', 'slug' => 'coolants'],
            ['name' => 'Greases', 'slug' => 'greases'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        $this->command->info('Categories seeded: ' . count($categories));
    }

    private function seedProducts(): void
    {
        $this->command->info('Seeding products...');

        $engineOils = Category::where('slug', 'engine-oils')->first();

        $products = [
            [
                'category_id' => $engineOils->id,
                'sku' => 'SF-MAX-20W50',
                'name' => 'SuperFuel Max 20W-50',
                'subcategory' => 'Mineral Oils',
                'description' => 'High-performance mineral oil for heavy-duty engines',
                'price' => 4500.00,
                'tax_rate' => 16.0,
                'unit' => 'Liter',
                'packaging' => '5L Container',
                'min_order_quantity' => 1,
                'reorder_level' => 30,
            ],
            [
                'category_id' => $engineOils->id,
                'sku' => 'ED-SYN-5W30',
                'name' => 'EcoDrive Synthetic 5W-30',
                'subcategory' => 'Synthetic Oils',
                'description' => 'Fully synthetic oil for modern passenger vehicles',
                'price' => 7200.00,
                'tax_rate' => 16.0,
                'unit' => 'Liter',
                'packaging' => '4L Container',
                'min_order_quantity' => 1,
                'reorder_level' => 40,
            ],
            [
                'category_id' => $engineOils->id,
                'sku' => 'PM-SEMI-10W40',
                'name' => 'ProMotor Semi-Synthetic 10W-40',
                'subcategory' => 'Semi-Synthetic Oils',
                'description' => 'Balanced performance for all-season protection',
                'price' => 5800.00,
                'tax_rate' => 16.0,
                'unit' => 'Liter',
                'packaging' => '4L Container',
                'min_order_quantity' => 1,
                'reorder_level' => 35,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        $this->command->info('Products seeded: ' . count($products));
    }

    private function seedCustomers(): void
    {
        $this->command->info('Seeding customers...');

        $customers = [
            [
                'name' => 'Quick Auto Services Ltd',
                'type' => 'Garage',
                'category' => 'A',
                'contact_person' => 'John Mwangi',
                'phone' => '+254-712-345678',
                'email' => 'info@quickautoservices.co.ke',
                'tax_id' => 'P051234567Q',
                'payment_terms' => 30,
                'credit_limit' => 500000.00,
                'current_balance' => 120000.00,
                'latitude' => -1.319370,
                'longitude' => 36.824120,
                'address' => 'Mombasa Road, Auto Plaza Building, Nairobi',
            ],
            [
                'name' => 'Premium Motors Kenya',
                'type' => 'Dealership',
                'category' => 'A+',
                'contact_person' => 'Sarah Wanjiku',
                'phone' => '+254-722-678901',
                'email' => 'sarah.w@premiummotors.co.ke',
                'tax_id' => 'P051345678R',
                'payment_terms' => 45,
                'credit_limit' => 1000000.00,
                'current_balance' => 450000.00,
                'latitude' => -1.292066,
                'longitude' => 36.821946,
                'address' => 'Uhuru Highway, Premium Towers, Nairobi',
            ],
            [
                'name' => 'Coast Logistics & Transport',
                'type' => 'Distributor',
                'category' => 'B',
                'contact_person' => 'Hassan Omar',
                'phone' => '+254-733-456789',
                'email' => 'hassan@coastlogistics.co.ke',
                'tax_id' => 'P051456789S',
                'payment_terms' => 30,
                'credit_limit' => 300000.00,
                'current_balance' => 85000.00,
                'latitude' => -4.043477,
                'longitude' => 39.668206,
                'address' => 'Nyerere Avenue, Mombasa',
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }

        $this->command->info('Customers seeded: ' . count($customers));
    }

    private function seedWarehouses(): void
    {
        $this->command->info('Seeding warehouses...');

        $warehouses = [
            [
                'code' => 'NCW',
                'name' => 'Nairobi Central Warehouse',
                'type' => 'Main',
                'address' => 'Enterprise Road, Industrial Area, Nairobi',
                'manager_email' => 'warehouse.nairobi@leysco.co.ke',
                'phone' => '+254-20-5551234',
                'capacity' => 50000,
                'latitude' => -1.308971,
                'longitude' => 36.851523,
            ],
            [
                'code' => 'MRW',
                'name' => 'Mombasa Regional Warehouse',
                'type' => 'Regional',
                'address' => 'Port Reitz Road, Changamwe, Mombasa',
                'manager_email' => 'warehouse.mombasa@leysco.co.ke',
                'phone' => '+254-41-2224567',
                'capacity' => 30000,
                'latitude' => -4.034396,
                'longitude' => 39.647446,
            ],
            [
                'code' => 'KSW',
                'name' => 'Kisumu Branch Warehouse',
                'type' => 'Branch',
                'address' => 'Jomo Kenyatta Highway, Kisumu',
                'manager_email' => 'warehouse.kisumu@leysco.co.ke',
                'phone' => '+254-57-2023456',
                'capacity' => 15000,
                'latitude' => -0.091702,
                'longitude' => 34.767956,
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::create($warehouse);
        }

        $this->command->info('Warehouses seeded: ' . count($warehouses));
    }

    private function seedInventory(): void
    {
        $this->command->info('Seeding inventory...');

        $products = Product::all();
        $warehouses = Warehouse::all();

        $count = 0;
        foreach ($products as $product) {
            foreach ($warehouses as $warehouse) {
                // Random stock between 50 and 200
                $quantity = rand(50, 200);
                
                Inventory::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'quantity' => $quantity,
                    'reserved_quantity' => 0,
                    'available_quantity' => $quantity,
                    'last_restock_date' => now()->subDays(rand(1, 30)),
                ]);
                
                $count++;
            }
        }

        $this->command->info('Inventory records seeded: ' . $count);
    }

    private function seedOrders(): void
    {
        $this->command->info('Seeding orders...');
        
        $orders = [
                        [
                'order_number' => 'ORD-' . strtoupper(Str::random(8)),
                'customer_id' => 1,
                'created_by' => 1,
                'status' => 'pending',
                'subtotal' => 12000.00,
                'discount_amount' => 0,
                'tax_amount' => 1920.00,
                'total_amount' => 13920.00,
                'notes' => 'First sample order, waiting for confirmation',
                'created_at' => Carbon::now()->subDays(5),
                'updated_at' => Carbon::now()->subDays(5),
            ],
            [
                'order_number' => 'ORD-' . strtoupper(Str::random(8)),
                'customer_id' => 2,
                'created_by' => 1,
                'status' => 'confirmed',
                'subtotal' => 8500.00,
                'discount_amount' => 500.00,
                'discount_type' => 'fixed',
                'discount_value' => 500.00,
                'tax_amount' => 1280.00,
                'total_amount' => 9280.00,
                'notes' => 'Confirmed order with a fixed discount',
                'confirmed_at' => Carbon::now()->subDays(3),
                'created_at' => Carbon::now()->subDays(4),
                'updated_at' => Carbon::now()->subDays(3),
            ],
            [
                'order_number' => 'ORD-' . strtoupper(Str::random(8)),
                'customer_id' => 3,
                'created_by' => 2,
                'status' => 'delivered',
                'subtotal' => 15000.00,
                'discount_amount' => 1500.00,
                'discount_type' => 'percentage',
                'discount_value' => 10.00,
                'tax_amount' => 2160.00,
                'total_amount' => 15660.00,
                'notes' => 'Delivered successfully with 10% discount applied',
                'confirmed_at' => Carbon::now()->subDays(7),
                'shipped_at' => Carbon::now()->subDays(5),
                'delivered_at' => Carbon::now()->subDays(2),
                'created_at' => Carbon::now()->subDays(8),
                'updated_at' => Carbon::now()->subDays(2),
            ],
        ];

        foreach ($orders as $orderData) {
            \App\Models\Order::create($orderData);
        }
    }

    private function seedOrderItems(): void
    {
        $this->command->info('Seeding order items...');

        $orders = \App\Models\Order::all();
        $products = \App\Models\Product::all();
        $warehouses = \App\Models\Warehouse::pluck('id')->toArray();

        $count = 0;

        foreach ($orders as $order) {
            foreach ($products->random(rand(1, 3)) as $product) { // random 1–3 products per order
                $quantity = rand(1, 10);
                $unitPrice = $product->price;
                $discountType = ['percentage', 'fixed'][array_rand(['percentage', 'fixed'])];
                $discountValue = $discountType === 'percentage' ? rand(5, 15) : rand(100, 500);
                $discountAmount = $discountType === 'percentage'
                    ? ($unitPrice * $quantity) * ($discountValue / 100)
                    : $discountValue;
                $subtotal = $unitPrice * $quantity;
                $taxRate = 16.0;
                $taxAmount = ($subtotal - $discountAmount) * ($taxRate / 100);
                $total = ($subtotal - $discountAmount) + $taxAmount;

                \App\Models\OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouses ? $warehouses[array_rand($warehouses)] : 1,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $discountAmount,
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'subtotal' => $subtotal,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'total' => $total,
                ]);

                $count++;
            }
        }

        $this->command->info("Order items seeded successfully: {$count}");
    }

}