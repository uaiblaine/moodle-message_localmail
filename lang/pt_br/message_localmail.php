<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language strings for the Local Mail message processor.
 *
 * @package    message_localmail
 * @copyright  2026 Anderson Blaine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['notification'] = 'Notificação';
$string['pluginname'] = 'Correio local';
$string['privacy:metadata'] = 'O processador de mensagens Correio local não armazena nenhum dado pessoal próprio. Ele entrega as notificações no plugin Local Mail, que as armazena, exporta e exclui sob o seu próprio provedor de privacidade. Como acontece com qualquer mensagem enviada pelo Correio local, uma cópia de cada notificação entregue também permanece visível para o usuário que a enviou.';
$string['systemsender'] = 'Conta remetente do sistema';
$string['systemsender_desc'] = 'Nome de usuário da conta a exibir como remetente quando uma notificação de curso vem de um usuário fictício, como o noreply ou o de suporte. Conclusão de curso, confirmações de envio de questionário e insights de análise são todos enviados assim, e são descartados enquanto este campo estiver vazio. A conta não precisa de inscrição nem de capacidade; uma cópia de cada notificação fica na pasta Enviados dela, então prefira uma conta dedicada a uma pessoa real.';
