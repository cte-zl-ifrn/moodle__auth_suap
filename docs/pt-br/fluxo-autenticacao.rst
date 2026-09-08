Fluxo de autenticação
======================

Resumo
------

1. ``login.php`` redireciona para ``authorize_url`` do SUAP com ``client_id`` e
   ``redirect_uri`` apontando para ``authenticate.php``.
2. O usuário se autentica no SUAP e é redirecionado de volta com um parâmetro ``code``.
3. ``authenticate.php`` troca o ``code`` por um ``access_token`` em ``token_url``.
4. Com o ``access_token``, o plugin busca dados em quatro endpoints do SUAP e faz o merge
   dos resultados.
5. ``create_or_update_user()`` cria ou atualiza o usuário no Moodle e sincroniza os campos de
   perfil.
6. ``complete_user_login()`` autentica a sessão e o usuário é redirecionado ao destino
   original (``next``/``wantsurl``).

Endpoints do plugin
--------------------

.. list-table::
   :header-rows: 1
   :widths: 30 70

   * - Endpoint
     - Finalidade
   * - ``/auth/suap/login.php``
     - Inicia o login SUAP (``auth_plugin_suap::login()``).
   * - ``/auth/suap/authenticate.php``
     - Callback OAuth2 (``auth_plugin_suap::authenticate()``).
   * - ``/auth/suap/logout.php``
     - Página de confirmação de logout completo (SUAP + Moodle).
   * - ``/auth/suap/health.php``
     - Diagnóstico: exibe configurações ativas (requer login).
   * - ``/auth/suap/index.php``
     - Redireciona para ``login.php``.

.. note::
   Versões anteriores deste README documentavam um endpoint adicional, ``dispatch.php``, que
   geraria um token de webservice do Moodle a partir de um header ``Authentication: Token``
   (para uso por aplicativos). Esse arquivo **não existe mais no código-fonte atual** —
   foi removido em uma refatoração anterior. Esta documentação descreve apenas o que está
   implementado hoje.

Passo 1 — ``login()``
-----------------------

``auth_plugin_suap::login()`` (em ``auth.php``):

* Resolve o destino pós-login (``next``): parâmetro ``next``, ou ``$SESSION->wantsurl``, ou a
  raiz do site.
* Se o usuário já está logado, apenas redireciona para ``next``.
* Caso contrário, valida que ``authorize_url`` e ``client_id`` estão configurados (senão
  lança ``configincomplete``), guarda ``next`` em ``$SESSION->next_after_next`` e redireciona
  para:

  .. code-block:: text

     {authorize_url}?response_type=code&client_id={client_id}&redirect_uri={wwwroot}/auth/suap/authenticate.php

Passo 2 — troca do código por token
--------------------------------------

``authenticate_token()`` faz um POST para ``token_url`` com ``grant_type=authorization_code``,
``code``, ``redirect_uri``, ``client_id`` e ``client_secret``. Em caso de erro (resposta sem
``access_token``, erro de cURL ou HTTP ≥ 400), a exceção é capturada e o plugin renderiza o
template ``auth_suap/auth_error`` com um botão para reiniciar o login, em vez de expor o
erro bruto ao usuário.

Se bem-sucedida, o método retorna os headers usados nas chamadas seguintes:

.. code-block:: text

   Authorization: Bearer {access_token}
   x-api-key: {client_secret}
   Accept: application/json

Passo 3 — coleta de dados do usuário
---------------------------------------

``authenticate()`` chama, em sequência, quatro métodos que consultam a API do SUAP e cujos
resultados são combinados com ``array_merge`` (nesta ordem: ``rheu``, ``meusdados``,
``ensinomeusdadosaluno``, ``meusvinculos`` — chaves posteriores sobrescrevem as anteriores):

.. list-table::
   :header-rows: 1
   :widths: 35 40 25

   * - Método
     - Endpoint (configurável)
     - Observações
   * - ``get_user_info_rh_eu()``
     - ``rh_eu_url`` (``.../api/rh/eu/``)
     - Chamado com ``scope=identificacao documentos_pessoais``. Lança exceção se a resposta
       não contiver ``"identificacao"``.
   * - ``get_user_info_rh_meus_dados()``
     - ``rh_meus_dados_url`` (``.../api/rh/meus-dados/``)
     - Remove o campo ``tipo_sanguineo`` da resposta antes de retornar.
   * - ``get_user_info_rh_meus_vinculos()``
     - ``rh_meus_vinculos_url`` (``.../api/rh/meus-vinculos/``)
     - Retorna ``{"vinculos": [...]}`` a partir de ``results``. Lança exceção se ``results``
       não for uma lista.
   * - ``get_user_info_ensino_meus_dados_aluno()``
     - ``ensino_meus_dados_aluno_url`` (``.../api/ensino/meus-dados-aluno/``)
     - **Tolerante a falhas**: qualquer exceção (ex.: usuário não é aluno) é capturada e o
       método retorna um objeto vazio, sem interromper o login. Remove
       ``email_academico``, ``email_escolar`` e ``cpf`` da resposta.

Passo 4 — criação/atualização do usuário
-------------------------------------------

Ver :doc:`sincronizacao-usuario` para a tabela completa de campos. Em resumo,
``create_or_update_user()``:

* Deriva o ``username`` de ``identificacao`` (ou ``matricula`` como *fallback*), em
  minúsculas; lança ``identificacao_ausente`` se nenhum dos dois estiver presente.
* Se o usuário não existir, cria a conta com uma senha aleatória local (ignorada, pois a
  autenticação é sempre via ``suap``) e, se ``local_suap`` estiver instalado, aplica
  ``default_user_preferences``.
* Em toda autenticação (criação ou login subsequente), atualiza nome, e-mail e dezenas de
  campos de perfil customizados a partir dos dados do SUAP.
* Persiste as alterações via ``update_user_record()`` (herdado de ``auth_oauth2\auth``).
* Se houver URL de foto disponível, baixa e aplica via ``update_picture()``.

Passo 5 — conclusão do login
-------------------------------

``complete_user_login($usuario)`` autentica a sessão Moodle. Em seguida,
``resolve_next_after_login()`` recupera e limpa ``$SESSION->next_after_next`` (definido no
passo 1) para determinar o destino do redirecionamento, com fallback para a raiz do site caso
nada tenha sido salvo.

Logout
------

``postlogout_hook()`` intercepta o logout de usuários com ``auth == 'suap'`` e redireciona
para ``/auth/suap/logout.php``, que exibe uma página de confirmação: o usuário escolhe entre
encerrar também a sessão no SUAP (``logout_url``) ou permanecer conectado ao Moodle.
``\core\session\manager::init_empty_session()`` é chamado antes de exibir a página, para já
invalidar a sessão Moodle local.
