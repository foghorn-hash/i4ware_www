# Google AI Search - Transactions Table Shortcode

Erillinen WordPress shortcode, joka luo hakukoneoptimoitu tapahtumataulu Polylang-tuella (suomi & englanti).

## Asennus

1. Lisää shortcode-koodi WordPress-teemaasi tai plugin-hakemistoihin
2. Vaihtoehtoisesti lisää koodi `functions.php` tiedostoon

```php
// WordPress theme functions.php tai plugin-tiedostoon
require_once( get_template_directory() . '/google-ai-shortcode.php' );
```

## Käyttö

Lisää shortcode sivulla tai artikkelissa:

```
[transactions_table_ai revenue_source="ALL" api_base_url="https://your-api.com"]
```

## Parametrit

| Parametri | Kuvaus | Oletusarvo |
|-----------|--------|-----------|
| `revenue_source` | Tulojen lähde (esim. "ALL", "GOOGLE") | ALL |
| `api_base_url` | API:n osoite | https://api.example.com |

## Kielituki

- **Suomi (fi)** - Polylang automaattisesti tunnistaa
- **Englanti (en)** - Oletuskieli

Käännökset:
- Kaikki tapahtumat / All Transactions
- Myyntipäivä / Sale Date
- Tulojen lähde / Revenue Source
- Toimittajan määrä / Vendor Amount

## SEO-ominaisuudet

✅ Suoraan renderöitu HTML (ei JavaScriptiä) - Google AI voi indeksoida  
✅ Strukturoidut tiedot (Schema.org)  
✅ Responsive taulu  
✅ Polylang-integraatio  
✅ WP Security - sanitoitu output

## Mukauttaminen

### CSS-luokat

```css
.transactions-table-ai        /* Wrapper */
.transactions-title           /* Otsikko */
.transactions-table           /* Taulu */
.text-start                   /* Tekstin tasaus */
```

### API-vastaus oletetaan muodossa

```json
{
  "root": [
    {
      "saleDate": "2024-01-15",
      "source": "Google AI",
      "revenueSource": "Google AI",
      "vendorAmount": "1250.50"
    }
  ]
}
```

## Google AI Search -optimointi

Shortcode tuottaa:
- Puhdas HTML-rakenne (ei React:ia)
- Selkeitä otsikkoja ja sisältöä
- Järkevät table-elementit
- Schema.org-metatiedot
- Käännettyä sisältöä

Google AI voi helposti indeksoida ja näyttää tämän sisällön hakutuloksissa.
