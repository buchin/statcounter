<?php
namespace Buchin\Statcounter;

describe("Statcounter", function () {
    given("sc", function () {
        $user = "user";
        $pass = "pass";

        $sc = new Statcounter($user, $pass);

        return $sc;
    });

    describe("validLogin()", function () {
        it("returns boolean", function () {
            $valid = $this->sc->valid_login();
            expect($valid)->toBe(true);
        });
    });

    describe("getKeywords()", function () {
        it("returns array of keywords", function () {
            $user = "user";
            $pass = "pass";

            $sc = new Statcounter($user, $pass);

            $keywords = $this->sc->getKeywords(['12702429']);

            var_dump($keywords, count($keywords));

            expect($keywords)->toBeA("array");
        });
    });
});
