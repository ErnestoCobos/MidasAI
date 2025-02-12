# Web Interface Implementation

## Architecture Overview

### Frontend (Vue.js 3 + Tailwind CSS)

#### 1. Authentication Pages
- Login
- Registration
- Password Reset
- Two-Factor Authentication

#### 2. Dashboard Layout
```vue
<!-- DashboardLayout.vue -->
<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Sidebar -->
    <nav class="fixed w-64 h-full bg-white shadow-lg">
      <div class="px-4 py-6">
        <h1 class="text-2xl font-bold">Midas Trading</h1>
        <!-- Navigation Menu -->
        <nav class="mt-8">
          <NavLink to="/dashboard">Dashboard</NavLink>
          <NavLink to="/trading">Trading</NavLink>
          <NavLink to="/portfolio">Portfolio</NavLink>
          <NavLink to="/strategies">Strategies</NavLink>
          <NavLink to="/analytics">Analytics</NavLink>
          <NavLink to="/settings">Settings</NavLink>
        </nav>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="ml-64 p-8">
      <router-view></router-view>
    </main>
  </div>
</template>
```

#### 3. Main Views

##### Dashboard View
```vue
<!-- DashboardView.vue -->
<template>
  <div>
    <!-- Portfolio Summary -->
    <div class="grid grid-cols-4 gap-6 mb-8">
      <StatCard
        title="Total Value"
        :value="portfolioValue"
        trend="up"
        :percentage="dailyChange"
      />
      <StatCard
        title="Open Positions"
        :value="openPositions"
      />
      <StatCard
        title="Daily P&L"
        :value="dailyPnL"
        :trend="dailyPnL >= 0 ? 'up' : 'down'"
      />
      <StatCard
        title="AI Win Rate"
        :value="aiMetrics.winRate"
        suffix="%"
      />
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-2 gap-6 mb-8">
      <PortfolioChart :data="portfolioHistory" />
      <AIPerformanceChart :data="aiPerformanceHistory" />
    </div>

    <!-- AI Analysis & Positions -->
    <div class="grid grid-cols-2 gap-6">
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold mb-4">Active Positions</h2>
        <PositionsTable :positions="activePositions" />
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold mb-4">AI Analysis</h2>
        <AIAnalysisPanel :analysis="latestAnalysis" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import StatCard from '@/components/StatCard.vue';
import PortfolioChart from '@/components/PortfolioChart.vue';
import AIPerformanceChart from '@/components/AIPerformanceChart.vue';
import PositionsTable from '@/components/PositionsTable.vue';
import AIAnalysisPanel from '@/components/AIAnalysisPanel.vue';

const aiMetrics = ref({
  winRate: 0,
  accuracy: 0,
  confidence: 0
});

const latestAnalysis = ref({
  marketRegime: '',
  riskLevel: 0,
  recommendations: []
});

const aiPerformanceHistory = ref([]);

onMounted(async () => {
  // Fetch AI metrics
  const metrics = await fetch('/api/ai/metrics').then(r => r.json());
  aiMetrics.value = metrics;

  // Fetch latest AI analysis
  const analysis = await fetch('/api/ai/analysis/latest').then(r => r.json());
  latestAnalysis.value = analysis;

  // Fetch AI performance history
  const history = await fetch('/api/ai/performance/history').then(r => r.json());
  aiPerformanceHistory.value = history;
});
</script>
```

##### Trading View
```vue
<!-- TradingView.vue -->
<template>
  <div>
    <!-- Trading Pair Selection -->
    <div class="flex items-center mb-6">
      <PairSelector v-model="selectedPair" />
      <TimeframeSelector v-model="timeframe" />
    </div>

    <!-- Trading Chart & AI Analysis -->
    <div class="grid grid-cols-3 gap-6 mb-6">
      <div class="col-span-2 bg-white rounded-lg shadow p-6">
        <TradingChart
          :pair="selectedPair"
          :timeframe="timeframe"
          :indicators="activeIndicators"
          :aiSignals="aiSignals"
        />
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold mb-4">AI Market Analysis</h2>
        <AIMarketAnalysis 
          :pair="selectedPair"
          :analysis="marketAnalysis"
        />
      </div>
    </div>

    <!-- Order Form & AI Validation -->
    <div class="grid grid-cols-3 gap-6">
      <OrderForm
        :pair="selectedPair"
        :price="currentPrice"
        :aiValidation="tradeValidation"
        @submit="validateAndPlaceOrder"
      />
      <OrderBook :pair="selectedPair" />
      <AITradeValidator
        :order="pendingOrder"
        :validation="tradeValidation"
        @approve="executeTrade"
        @reject="cancelTrade"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import PairSelector from '@/components/PairSelector.vue';
import TimeframeSelector from '@/components/TimeframeSelector.vue';
import TradingChart from '@/components/TradingChart.vue';
import OrderForm from '@/components/OrderForm.vue';
import OrderBook from '@/components/OrderBook.vue';
import AIMarketAnalysis from '@/components/AIMarketAnalysis.vue';
import AITradeValidator from '@/components/AITradeValidator.vue';

const selectedPair = ref(null);
const timeframe = ref('1h');
const pendingOrder = ref(null);
const marketAnalysis = ref(null);
const tradeValidation = ref(null);
const aiSignals = ref([]);

watch(selectedPair, async (pair) => {
  if (pair) {
    // Fetch AI market analysis
    marketAnalysis.value = await fetch(`/api/ai/analysis/${pair}`).then(r => r.json());
    // Fetch AI trading signals
    aiSignals.value = await fetch(`/api/ai/signals/${pair}`).then(r => r.json());
  }
});

async function validateAndPlaceOrder(order) {
  pendingOrder.value = order;
  // Get AI validation
  tradeValidation.value = await fetch('/api/ai/validate-trade', {
    method: 'POST',
    body: JSON.stringify(order)
  }).then(r => r.json());
}

async function executeTrade() {
  if (pendingOrder.value && tradeValidation.value.approved) {
    await placeOrder(pendingOrder.value);
    pendingOrder.value = null;
    tradeValidation.value = null;
  }
}
</script>
```

##### Strategy Management
```vue
<!-- StrategyView.vue -->
<template>
  <div>
    <!-- Strategy List -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Trading Strategies</h2>
        <button
          @click="createStrategy"
          class="btn-primary"
        >
          New Strategy
        </button>
      </div>
      <StrategyTable
        :strategies="strategies"
        @edit="editStrategy"
        @delete="deleteStrategy"
      />
    </div>

    <!-- Strategy Editor Modal -->
    <StrategyEditor
      v-if="showEditor"
      :strategy="selectedStrategy"
      @save="saveStrategy"
      @cancel="closeEditor"
    />
  </div>
</template>
```

### Backend (Laravel)

#### 1. Authentication Controllers
```php
class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        // Validate credentials
        if (!Auth::attempt($request->validated())) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Generate token
        $token = $request->user()->createToken('trading-app');

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => $request->user()
        ]);
    }

    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Assign default role
        $user->assignRole('trader');

        return response()->json([
            'message' => 'User registered successfully'
        ]);
    }
}
```

#### 2. Trading Controllers
```php
class TradingController extends Controller
{
    public function placeOrder(OrderRequest $request)
    {
        // Validate user can trade
        $this->authorize('trade');

        // Create order
        $order = Order::create([
            'user_id' => auth()->id(),
            'trading_pair_id' => $request->trading_pair_id,
            'type' => $request->type,
            'side' => $request->side,
            'quantity' => $request->quantity,
            'price' => $request->price,
        ]);

        // Execute order
        $this->orderService->execute($order);

        return response()->json($order);
    }

    public function getPositions()
    {
        return response()->json(
            Position::with('tradingPair')
                ->where('user_id', auth()->id())
                ->where('status', 'open')
                ->get()
        );
    }
}
```

#### 3. Strategy Controllers
```php
class StrategyController extends Controller
{
    public function index()
    {
        return response()->json(
            TradingStrategy::where('user_id', auth()->id())->get()
        );
    }

    public function store(StrategyRequest $request)
    {
        $strategy = TradingStrategy::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'description' => $request->description,
            'parameters' => $request->parameters,
            'is_active' => false,
        ]);

        return response()->json($strategy);
    }

    public function update(StrategyRequest $request, TradingStrategy $strategy)
    {
        $this->authorize('update', $strategy);

        $strategy->update($request->validated());

        return response()->json($strategy);
    }
}
```

### WebSocket Integration

#### 1. Broadcasting Setup
```php
// config/broadcasting.php
return [
    'default' => env('BROADCAST_DRIVER', 'pusher'),
    'connections' => [
        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'encrypted' => true,
                'host' => '127.0.0.1',
                'port' => 6001,
                'scheme' => 'http'
            ],
        ],
    ],
];
```

#### 2. Event Broadcasting
```php
class MarketDataUpdated implements ShouldBroadcast
{
    public $tradingPair;
    public $data;

    public function __construct(TradingPair $tradingPair, array $data)
    {
        $this->tradingPair = $tradingPair;
        $this->data = $data;
    }

    public function broadcastOn()
    {
        return new Channel('market-data.' . $this->tradingPair->symbol);
    }
}
```

#### 3. Frontend WebSocket Integration
```typescript
// useWebSocket.ts
export function useWebSocket(symbol: string) {
    const echo = new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
        wsHost: import.meta.env.VITE_WS_HOST,
        wsPort: import.meta.env.VITE_WS_PORT,
        forceTLS: false,
        disableStats: true,
    });

    onMounted(() => {
        echo.channel(`market-data.${symbol}`)
            .listen('MarketDataUpdated', (e: any) => {
                // Update chart data
                updateChartData(e.data);
                // Update order book
                updateOrderBook(e.data.orderBook);
                // Update ticker
                updateTicker(e.data.ticker);
            });
    });

    onUnmounted(() => {
        echo.leaveChannel(`market-data.${symbol}`);
    });
}
```

### Security Implementation

#### 1. Authentication Middleware
```php
class Authenticate extends Middleware
{
    protected function authenticate($request, array $guards)
    {
        if ($this->auth->guard('sanctum')->check()) {
            return $this->auth->shouldUse('sanctum');
        }

        $this->unauthenticated($request, ['sanctum']);
    }
}
```

#### 2. Role & Permission System
```php
class RoleAndPermissionSeeder extends Seeder
{
    public function run()
    {
        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'trader']);
        Role::create(['name' => 'viewer']);

        // Create permissions
        Permission::create(['name' => 'trade']);
        Permission::create(['name' => 'manage_strategies']);
        Permission::create(['name' => 'view_portfolio']);
        Permission::create(['name' => 'manage_users']);

        // Assign permissions to roles
        Role::findByName('admin')->givePermissionTo(Permission::all());
        Role::findByName('trader')->givePermissionTo(['trade', 'manage_strategies', 'view_portfolio']);
        Role::findByName('viewer')->givePermissionTo(['view_portfolio']);
    }
}
```

#### 3. API Rate Limiting
```php
// routes/api.php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/market-data', [MarketDataController::class, 'index']);
    Route::post('/orders', [TradingController::class, 'placeOrder']);
});

Route::middleware(['auth:sanctum', 'throttle:1000,1'])->group(function () {
    Route::get('/portfolio', [PortfolioController::class, 'index']);
    Route::get('/positions', [TradingController::class, 'getPositions']);
});
```

### Error Handling

#### 1. Global Error Handler
```typescript
// errorHandler.ts
export function setupErrorHandler(app: App) {
    app.config.errorHandler = (err, vm, info) => {
        // Log error
        console.error(err);

        // Show user-friendly notification
        notify({
            type: 'error',
            title: 'Error',
            message: getErrorMessage(err)
        });

        // Report to monitoring service
        reportError(err, info);
    };
}
```

#### 2. API Error Responses
```php
class Handler extends ExceptionHandler
{
    public function render($request, Throwable $exception)
    {
        if ($request->expectsJson()) {
            if ($exception instanceof ValidationException) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $exception->errors()
                ], 422);
            }

            if ($exception instanceof AuthenticationException) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }

            if ($exception instanceof AuthorizationException) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 403);
            }
        }

        return parent::render($request, $exception);
    }
}
```

### Performance Optimization

#### 1. Caching Strategy
```php
class PortfolioController extends Controller
{
    public function getMetrics()
    {
        return Cache::remember(
            'portfolio_metrics_' . auth()->id(),
            now()->addMinutes(5),
            function () {
                return $this->calculatePortfolioMetrics();
            }
        );
    }
}
```

#### 2. Asset Optimization
```javascript
// vite.config.js
export default defineConfig({
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'trading-chart': ['trading-vue-js'],
                    'vendor': ['vue', 'vue-router', 'pinia']
                }
            }
        },
        chunkSizeWarningLimit: 1000
    }
});
```

#### 3. API Response Optimization
```php
class TradingPairResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'symbol' => $this->symbol,
            'price' => $this->when($request->includes('price'), 
                fn() => $this->getLatestPrice()
            ),
            'indicators' => $this->when($request->includes('indicators'),
                fn() => $this->getIndicators()
            )
        ];
    }
}
