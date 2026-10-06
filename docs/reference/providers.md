# Providers

All built-in providers live in `Fomvasss\Currency\RateProviders`, extend `AbstractRateProvider` and have the base currency `UAH`. Every request uses a 10-second timeout.

| Alias | Class | Implements `HistoricalRateProvider` | Constructor |
|---|---|---|---|
| `monobank` | `MonobankRateProvider` | no | — |
| `privatbank` | `PrivatbankRateProvider` | yes | — |
| `nbu` | `NbuRateProvider` | yes | — |
| `jsdelivr` | `JsDelivrProvider` | yes | — |
| `exchangeratesapi` | `ExchangeRatesApiProvider` | yes | `(?string $apiKey = null, string $baseCurrency = 'UAH')` |
| `currencyapi` | `CurrencyApiProvider` | yes | `(?string $apiKey = null, string $baseCurrency = 'UAH')` |
| `fixer` | `FixerProvider` | yes | `(?string $apiKey = null, string $baseCurrency = 'UAH')` |

> [!WARNING]
> `exchangeratesapi`, `currencyapi` and `fixer` store the API's "currency per 1 base unit" values as is, while `Currency` expects "base per 1 currency unit" — conversions with them are inverted. See [Rate providers](../usage/providers.md#built-in-providers) and the [workaround](../usage/custom-providers.md#inverting-a-providers-rates).

## monobank

- URL: `https://api.monobank.ua/bank/currency`
- Only pairs against UAH (`currencyCodeB = 980`) are used.
- Currencies (by ISO numeric code): USD, EUR, PLN, GBP, CAD, CZK, DKK, HUF, JPY, NOK, SEK, CHF, AUD, CNY, TRY. Other currencies in the response are ignored.
- `buy` = `rateBuy`, `sell` = `rateSell`; when the API gives only `rateCross`, both are `rateCross`. Entries with a zero rate are skipped.
- No historical rates.

## privatbank

- Current: `https://api.privatbank.ua/p24api/pubinfo?exchange&coursid=5` — EUR and USD only (`buy`, `sale`), entries with `base_ccy = UAH`.
- Historical: `https://api.privatbank.ua/p24api/exchange_rates?json&date=d.m.Y`; only entries with the bank's own `purchaseRate` and `saleRate` are used — the `*NB` (NBU) fields are ignored.

## nbu

- Current: `https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?json`
- Historical: the same URL with `&date=Ymd`.
- All entries with `cc` and `rate`; `buy` = `sell` = the official rate.

## jsdelivr

- Current: `https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/uah.json`
- Historical: `@latest` replaced by `@Y-m-d`; available from 2024-03-06, earlier dates return 404 (no rates).
- The API gives "currency per 1 UAH"; the provider inverts it.
- Buy/sell are synthetic: mid rate × (1 ∓ `$spread` / 2), `protected float $spread = 0.01` — i.e. ±0.5%. Override `$spread` in a subclass (`0` for mid rates).
- Includes every code the API publishes; entries with zero rates are skipped.
- No static fallback rates.

## exchangeratesapi

- With `exchange_rates_api_key`: `https://api.exchangeratesapi.io/v1/latest?access_key=KEY&base=BASE`, historical `.../v1/Y-m-d?...`.
- Without a key: `https://api.frankfurter.dev/v1/latest?from=BASE`, historical `.../v1/Y-m-d?from=BASE`. Frankfurter supports ECB currencies only — not `UAH`, the default base.
- `buy` = `sell` = the value from `rates`.

## currencyapi

- `https://api.currencyapi.com/v3/latest?apikey=KEY&base_currency=BASE`, historical `.../v3/historical?...&date=Y-m-d`.
- `buy` = `sell` = `data.CODE.value`.

## fixer

- `https://data.fixer.io/api/latest?access_key=KEY&base=BASE`, historical `https://data.fixer.io/api/Y-m-d?...`.
- Rates are read only when the response has `"success": true`.
- Fixer's free plan works over HTTP with `base=EUR` only; the provider always uses HTTPS and `base=UAH` by default, so it needs a paid plan, or a [rebinding](../usage/providers.md#constructor-arguments) with `'EUR'` plus a plan that allows HTTPS.

## AbstractRateProvider

Public methods available on every built-in provider (in addition to the [contracts](contracts.md)):

| Method | Description |
|---|---|
| `getRatesAt(DateTimeInterface $date): array` | Historical rates; throws `LogicException` unless the class overrides `getHistoricalApiUrl()` |
| `getRateAt(string $currency, DateTimeInterface $date): ?array` | One currency from `getRatesAt()` |
| `setCacheTtl(int $seconds): static` | Override `cache_ttl` for this instance |

Protected extension points — [Custom providers](../usage/custom-providers.md).
