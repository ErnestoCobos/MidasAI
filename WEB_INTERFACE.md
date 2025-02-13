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

#### 1. Nova Resources

```php
// app/Nova/User.php
class User extends Resource
{
    public static $model = \App\Models\User::class;

    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            Text::make('Name')->sortable(),
            Text::make('Email')->sortable(),
            Password::make('Password')->onlyOnForms(),
            Select::make('Role')->options([
                User::ROLE_ADMIN => 'Administrator',
                User::ROLE_TRADER => 'Trader',
                User::ROLE_VIEWER => 'Viewer'
            ])->displayUsingLabels(),
            Boolean::make('Is Active'),
            HasMany::make('Orders'),
            HasMany::make('Positions'),
            HasMany::make('TradingStrategies'),
        ];
    }

    public function cards(Request $request)
    {
        return [
            new Metrics\UserPortfolioValue,
            new Metrics\UserTradingVolume,
            new Metrics\UserProfitLoss,
            new Metrics\UserTradingAccuracy,
        ];
    }

    public function authorizedToView(Request $request)
    {
        return $request->user()->can('view_analytics');
    }

    public function authorizedToCreate(Request $request)
    {
        return $request->user()->role === User::ROLE_ADMIN;
    }

    public function authorizedToUpdate(Request $request)
    {
        return $request->user()->role === User::ROLE_ADMIN;
    }

    public function authorizedToDelete(Request $request)
    {
        return $request->user()->role === User::ROLE_ADMIN;
    }
}

// app/Nova/Order.php
class Order extends Resource
{
    public static $model = \App\Models\Order::class;

    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('User'),
            BelongsTo::make('TradingPair'),
            Select::make('Type')->options([
                'market' => 'Market',
                'limit' => 'Limit',
                'stop_loss' => 'Stop Loss',
                'take_profit' => 'Take Profit'
            ])->displayUsingLabels(),
            Select::make('Side')->options([
                'buy' => 'Buy',
                'sell' => 'Sell'
            ])->displayUsingLabels(),
            Number::make('Quantity'),
            Number::make('Price'),
            Number::make('Total')->exceptOnForms(),
            Status::make('Status')
                ->loadingWhen(['pending'])
                ->failedWhen(['failed'])
                ->successWhen(['completed']),
            DateTime::make('Created At')->sortable(),
        ];
    }

    public function actions(Request $request)
    {
        return [
            new Actions\CancelOrder,
            new Actions\ModifyOrder,
        ];
    }
}

// app/Nova/TradingStrategy.php
class TradingStrategy extends Resource
{
    public static $model = \App\Models\TradingStrategy::class;

    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            Text::make('Name')->sortable(),
            Textarea::make('Description'),
            Code::make('Parameters')->json(),
            Boolean::make('Is Active'),
            BelongsTo::make('User'),
            HasMany::make('Orders'),
            DateTime::make('Created At')->sortable(),
            DateTime::make('Updated At')->sortable(),
        ];
    }

    public function actions(Request $request)
    {
        return [
            new Actions\ExecuteStrategy,
            new Actions\BacktestStrategy,
            new Actions\OptimizeStrategy,
        ];
    }

    public function cards(Request $request)
    {
        return [
            new Metrics\StrategyPerformance,
            new Metrics\StrategyAccuracy,
            new Metrics\StrategyRiskMetrics,
        ];
    }
}
```

#### 2. Nova Actions

```php
// app/Nova/Actions/ExecuteStrategy.php
class ExecuteStrategy extends Action
{
    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $strategy) {
            if ($fields->backtest_only) {
                dispatch(new BacktestStrategyJob($strategy));
            } else {
                dispatch(new ExecuteStrategyJob($strategy, [
                    'risk_level' => $fields->risk_level
                ]));
            }
        }

        return Action::message('Strategy execution initiated');
    }

    public function fields(Request $request)
    {
        return [
            Boolean::make('Backtest Only'),
            Number::make('Risk Level')
                ->min(1)
                ->max(10)
                ->help('Set the risk level for strategy execution'),
        ];
    }
}

// app/Nova/Actions/ModifyOrder.php
class ModifyOrder extends Action
{
    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $order) {
            $order->update([
                'quantity' => $fields->quantity,
                'price' => $fields->price,
            ]);
        }

        return Action::message('Orders updated successfully');
    }

    public function fields(Request $request)
    {
        return [
            Number::make('Quantity')
                ->rules('required', 'min:0'),
            Number::make('Price')
                ->rules('required', 'min:0'),
        ];
    }
}
```

#### 3. Nova Metrics

```php
// app/Nova/Metrics/PortfolioValue.php
class PortfolioValue extends Value
{
    public function calculate(Request $request)
    {
        return $this->result(
            PortfolioSnapshot::latest()->first()->total_value
        )->currency('USD');
    }

    public function ranges()
    {
        return [
            'TODAY' => 'Today',
            30 => '30 Days',
            60 => '60 Days',
            365 => '365 Days',
        ];
    }
}

// app/Nova/Metrics/TradingVolume.php
class TradingVolume extends Trend
{
    public function calculate(Request $request)
    {
        return $this->sumByDays($request, Order::class, 'total')
            ->showLatestValue()
            ->suffix('USD');
    }
}

// app/Nova/Metrics/AIAccuracy.php
class AIAccuracy extends Partition
{
    public function calculate(Request $request)
    {
        return $this->count($request, AIDecision::class, 'accuracy_level')
            ->label(function($value) {
                return ["High", "Medium", "Low"][$value] ?? $value;
            });
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

#### 1. Nova Authorization

```php
// app/Providers/NovaServiceProvider.php
class NovaServiceProvider extends NovaApplicationServiceProvider
{
    protected function gate()
    {
        Gate::define('viewNova', function ($user) {
            return $user->can('view_analytics');
        });
    }

    protected function resources()
    {
        Nova::resources([
            User::class,
            TradingPair::class,
            Order::class,
            Position::class,
            TradingStrategy::class,
            SystemLog::class,
            TechnicalIndicator::class,
            MarketData::class,
            SentimentData::class,
            PortfolioSnapshot::class,
        ]);
    }

    protected function cards()
    {
        return [
            new Metrics\PortfolioValue,
            new Metrics\TradingVolume,
            new Metrics\AIAccuracy,
            new Metrics\SystemHealth,
            new Metrics\ProfitLoss,
            new Metrics\ActivePositions,
            new Metrics\SignalOccurrences,
            new Metrics\LogLevelDistribution,
        ];
    }

    protected function tools()
    {
        return [
            new TradingDashboard,
            new AIMonitor,
            new RiskManager,
            new SystemMonitor,
            new StrategyManager,
            new LogViewer,
        ];
    }
}

// app/Policies/TradingStrategyPolicy.php
class TradingStrategyPolicy
{
    public function viewAny(User $user)
    {
        return $user->can('view_analytics');
    }

    public function view(User $user, TradingStrategy $strategy)
    {
        return $user->can('view_analytics') && 
            ($user->id === $strategy->user_id || $user->role === User::ROLE_ADMIN);
    }

    public function create(User $user)
    {
        return $user->can('manage_strategies');
    }

    public function update(User $user, TradingStrategy $strategy)
    {
        return $user->can('manage_strategies') && 
            ($user->id === $strategy->user_id || $user->role === User::ROLE_ADMIN);
    }

    public function delete(User $user, TradingStrategy $strategy)
    {
        return $user->role === User::ROLE_ADMIN;
    }

    public function restore(User $user, TradingStrategy $strategy)
    {
        return $user->role === User::ROLE_ADMIN;
    }

    public function forceDelete(User $user, TradingStrategy $strategy)
    {
        return $user->role === User::ROLE_ADMIN;
    }
}
```

#### 2. Nova Role & Permission System

```php
// app/Models/User.php
class User extends Authenticatable
{
    const ROLE_ADMIN = 'admin';
    const ROLE_TRADER = 'trader';
    const ROLE_VIEWER = 'viewer';

    public function can($permission): bool
    {
        // Admin has all permissions
        if ($this->email === 'ernesto@cobos.io') {
            return true;
        }

        // Role-based permissions
        $permissions = [
            self::ROLE_ADMIN => [
                'manage_strategies',
                'manage_trading_pairs',
                'view_logs',
                'view_analytics',
            ],
            self::ROLE_TRADER => [
                'view_analytics',
                'view_logs',
            ],
            self::ROLE_VIEWER => [
                'view_analytics',
            ],
        ];

        return in_array($permission, $permissions[$this->role] ?? []);
    }
}

// database/migrations/2025_02_13_043338_add_role_to_users_table.php
class AddRoleToUsersTable extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('viewer');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
}

// database/seeders/DatabaseSeeder.php
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@midas.trade',
            'role' => User::ROLE_ADMIN,
        ]);

        User::factory()->create([
            'name' => 'Ernesto Cobos',
            'email' => 'ernesto@cobos.io',
            'password' => bcrypt('Aa121292#1221#'),
            'role' => User::ROLE_ADMIN,
        ]);
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
