<?php

use App\Services\LevelService;

it('requires 100 xp times the level to pass it', function () {
    expect(LevelService::xpParaProximoNivel(1))->toBe(100);
    expect(LevelService::xpParaProximoNivel(2))->toBe(200);
    expect(LevelService::xpParaProximoNivel(27))->toBe(2700);
});

it('ranks by level bands of ten, from E to S', function () {
    expect(LevelService::rankDoNivel(1))->toBe('E');
    expect(LevelService::rankDoNivel(9))->toBe('E');
    expect(LevelService::rankDoNivel(10))->toBe('D');
    expect(LevelService::rankDoNivel(19))->toBe('D');
    expect(LevelService::rankDoNivel(20))->toBe('C');
    expect(LevelService::rankDoNivel(29))->toBe('C');
    expect(LevelService::rankDoNivel(30))->toBe('B');
    expect(LevelService::rankDoNivel(39))->toBe('B');
    expect(LevelService::rankDoNivel(40))->toBe('A');
    expect(LevelService::rankDoNivel(49))->toBe('A');
    expect(LevelService::rankDoNivel(50))->toBe('S');
    expect(LevelService::rankDoNivel(120))->toBe('S');
});

it('calculates level, xp within level and rank from total xp', function () {
    expect(LevelService::calcular(0))->toBe(['nivel' => 1, 'xp' => 0, 'xp_proximo' => 100, 'rank' => 'E']);
    expect(LevelService::calcular(99))->toBe(['nivel' => 1, 'xp' => 99, 'xp_proximo' => 100, 'rank' => 'E']);
    // 100 xp passa o nível 1; sobram 0 dentro do nível 2, que pede 200
    expect(LevelService::calcular(100))->toBe(['nivel' => 2, 'xp' => 0, 'xp_proximo' => 200, 'rank' => 'E']);
    // 100 (nível 1) + 200 (nível 2) = 300 para chegar ao nível 3
    expect(LevelService::calcular(300))->toBe(['nivel' => 3, 'xp' => 0, 'xp_proximo' => 300, 'rank' => 'E']);
});
