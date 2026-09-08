<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Unit tests for auth_plugin_suap.
 *
 * @package    auth_suap
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_suap;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/auth/suap/auth.php');

/**
 * Tests for auth_plugin_suap::resolve_next_after_login().
 *
 * @package    auth_suap
 * @copyright  2026 Kelson Medeiros <kelsoncm@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \auth_plugin_suap
 */
final class auth_test extends \advanced_testcase {
    /**
     * Set up the test environment.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Invoca um método protegido/privado via reflection.
     *
     * @param object $object Instância alvo.
     * @param string $methodname Nome do método.
     * @param array $args Argumentos posicionais.
     * @return mixed
     */
    protected function call_protected($object, $methodname, array $args = []) {
        $method = new \ReflectionMethod($object, $methodname);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }

    /**
     * Reproduz o bug relatado: após voltar do login no SUAP (authenticate()), o usuário deve ser
     * redirecionado para a página que originalmente tentou acessar (armazenada por login() em
     * $SESSION->next_after_next), e não sempre para a home do site.
     */
    public function test_resolve_next_after_login_restores_original_destination(): void {
        global $SESSION;

        $destino = 'https://exemplo.org/course/view.php?id=5';
        $SESSION->next_after_next = $destino;

        $plugin = new \auth_plugin_suap();
        $next = $this->call_protected($plugin, 'resolve_next_after_login');

        $this->assertSame($destino, $next);
    }

    /**
     * Sem destino salvo (ex.: acesso direto à home antes do login), o fallback é a home do site.
     */
    public function test_resolve_next_after_login_falls_back_to_wwwroot(): void {
        global $CFG, $SESSION;

        unset($SESSION->next_after_next);

        $plugin = new \auth_plugin_suap();
        $next = $this->call_protected($plugin, 'resolve_next_after_login');

        $this->assertSame($CFG->wwwroot, $next);
    }

    /**
     * O destino salvo deve ser consumido uma única vez, para não vazar entre logins.
     */
    public function test_resolve_next_after_login_clears_session_value(): void {
        global $SESSION;

        $SESSION->next_after_next = 'https://exemplo.org/course/view.php?id=5';

        $plugin = new \auth_plugin_suap();
        $this->call_protected($plugin, 'resolve_next_after_login');

        $this->assertTrue(empty($SESSION->next_after_next));
    }
}
