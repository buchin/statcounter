# buchin/statcounter

Extract keyword using statcounter API

## Installation

composer require buchin/statcounter

## Usage

```
use Buchin\Statcounter\Statcounter;

$sc = new Statcounter('username', 'api_password');

$top_keywords = $sc->getKeywords(1000, 'top');

$recent_keywords = $sc->getKeywords(1000, 'recent');
```

## Test

```
./vendor/bin/kahlan --reporter=verbose
```
