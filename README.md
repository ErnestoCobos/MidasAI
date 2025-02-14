# Midas Trading Bot

![Logo of the Midas, created in Procreate](public/image.png)

## Overview
Midas is a sophisticated automated trading solution that combines technical analysis, sentiment analysis, and AI-powered decision making to execute trades on the Binance platform. Built with Laravel and Vue.js, it offers a secure and intuitive web interface for managing your trading strategies.

## Key Features

### AI-Powered Trading
- Integration with DeepSeek-R1 for advanced market analysis
- Pattern recognition and market regime identification
- Risk assessment and decision validation
- Confidence scoring system for trade execution

### Technical Analysis
- Multiple technical indicators (RSI, MACD, Bollinger Bands, ATR)
- Custom strategy development and backtesting
- Real-time market data analysis
- Automated signal generation

### Sentiment Analysis
- Integration with multiple news sources and social media
- VADER sentiment analysis
- Topic classification and entity recognition
- Market sentiment trend tracking

### Risk Management
- Dynamic position sizing
- Portfolio exposure controls
- Drawdown protection
- Volatility-based adjustments

### Web Interface
- Secure authentication with 2FA
- Real-time portfolio monitoring
- Strategy configuration and management
- Performance analytics dashboard

## Technology Stack
- Backend: Laravel 11
- Frontend: Vue.js 3 + Tailwind CSS
- Admin Panel: Laravel Nova
- Real-time Updates: Laravel Echo
- Queue Management: Laravel Horizon
- AI Integration: DeepSeek-R1

## Getting Started

### Prerequisites
- PHP 8.1 or higher
- Node.js 22 or higher
- Composer
- PostgreSQL

### Installation
1. Clone the repository
```bash
git clone https://github.com/ErnestoCobos/midas.git
cd midas
```

2. Install PHP dependencies
```bash
composer install
```

3. Install JavaScript dependencies
```bash
npm install
```

4. Configure environment
```bash
cp .env.example .env
php artisan key:generate
```

5. Set up the database
```bash
php artisan migrate
php artisan db:seed
```

6. Start the development server
```bash
php artisan serve
npm run dev
```

## Security
- Role-based access control
- Two-factor authentication
- API key management
- Comprehensive audit logging

## Contributing
Contributions are welcome! Please read our contributing guidelines before submitting pull requests.

## License
This project is licensed under the MIT License - see the LICENSE file for details.
