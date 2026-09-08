Authentication flow
====================

Summary
-------

1. ``login.php`` redirects to SUAP's ``authorize_url`` with ``client_id`` and
   ``redirect_uri`` pointing to ``authenticate.php``.
2. The user authenticates in SUAP and is redirected back with a ``code`` parameter.
3. ``authenticate.php`` exchanges the ``code`` for an ``access_token`` at ``token_url``.
4. With the ``access_token``, the plugin fetches data from four SUAP endpoints and merges
   the results.
5. ``create_or_update_user()`` creates or updates the user in Moodle and synchronizes the
   profile fields.
6. ``complete_user_login()`` authenticates the session and the user is redirected to the
   original destination (``next``/``wantsurl``).

Plugin endpoints
------------------

.. list-table::
   :header-rows: 1
   :widths: 30 70

   * - Endpoint
     - Purpose
   * - ``/auth/suap/login.php``
     - Starts the SUAP login (``auth_plugin_suap::login()``).
   * - ``/auth/suap/authenticate.php``
     - OAuth2 callback (``auth_plugin_suap::authenticate()``).
   * - ``/auth/suap/logout.php``
     - Full logout confirmation page (SUAP + Moodle).
   * - ``/auth/suap/health.php``
     - Diagnostics: shows active settings (requires login).
   * - ``/auth/suap/index.php``
     - Redirects to ``login.php``.

.. note::
   Earlier versions of this README documented an additional endpoint, ``dispatch.php``, which
   would generate a Moodle webservice token from an ``Authentication: Token`` header (for use
   by applications). This file **no longer exists in the current source code** — it was
   removed in a previous refactor. This documentation describes only what is implemented
   today.

Step 1 — ``login()``
----------------------

``auth_plugin_suap::login()`` (in ``auth.php``):

* Resolves the post-login destination (``next``): the ``next`` parameter, or
  ``$SESSION->wantsurl``, or the site root.
* If the user is already logged in, it simply redirects to ``next``.
* Otherwise, it validates that ``authorize_url`` and ``client_id`` are configured (otherwise
  it throws ``configincomplete``), stores ``next`` in ``$SESSION->next_after_next`` and
  redirects to:

  .. code-block:: text

     {authorize_url}?response_type=code&client_id={client_id}&redirect_uri={wwwroot}/auth/suap/authenticate.php

Step 2 — exchanging the code for a token
-------------------------------------------

``authenticate_token()`` makes a POST request to ``token_url`` with
``grant_type=authorization_code``, ``code``, ``redirect_uri``, ``client_id`` and
``client_secret``. On error (a response without ``access_token``, a cURL error, or HTTP ≥
400), the exception is caught and the plugin renders the ``auth_suap/auth_error`` template
with a button to restart the login, instead of exposing the raw error to the user.

On success, the method returns the headers used in the following calls:

.. code-block:: text

   Authorization: Bearer {access_token}
   x-api-key: {client_secret}
   Accept: application/json

Step 3 — collecting user data
--------------------------------

``authenticate()`` calls, in sequence, four methods that query the SUAP API and whose results
are combined with ``array_merge`` (in this order: ``rheu``, ``meusdados``,
``ensinomeusdadosaluno``, ``meusvinculos`` — later keys overwrite earlier ones):

.. list-table::
   :header-rows: 1
   :widths: 35 40 25

   * - Method
     - Endpoint (configurable)
     - Notes
   * - ``get_user_info_rh_eu()``
     - ``rh_eu_url`` (``.../api/rh/eu/``)
     - Called with ``scope=identificacao documentos_pessoais``. Throws an exception if the
       response does not contain ``"identificacao"``.
   * - ``get_user_info_rh_meus_dados()``
     - ``rh_meus_dados_url`` (``.../api/rh/meus-dados/``)
     - Removes the ``tipo_sanguineo`` field from the response before returning it.
   * - ``get_user_info_rh_meus_vinculos()``
     - ``rh_meus_vinculos_url`` (``.../api/rh/meus-vinculos/``)
     - Returns ``{"vinculos": [...]}`` from ``results``. Throws an exception if ``results``
       is not a list.
   * - ``get_user_info_ensino_meus_dados_aluno()``
     - ``ensino_meus_dados_aluno_url`` (``.../api/ensino/meus-dados-aluno/``)
     - **Fault-tolerant**: any exception (e.g., the user is not a student) is caught and the
       method returns an empty object, without interrupting the login. Removes
       ``email_academico``, ``email_escolar`` and ``cpf`` from the response.

Step 4 — user creation/update
--------------------------------

See :doc:`sincronizacao-usuario` for the complete field table. In summary,
``create_or_update_user()``:

* Derives ``username`` from ``identificacao`` (or ``matricula`` as a *fallback*), lowercased;
  throws ``identificacao_ausente`` if neither is present.
* If the user does not exist, it creates the account with a random local password (ignored,
  since authentication is always done via ``suap``) and, if ``local_suap`` is installed,
  applies ``default_user_preferences``.
* On every authentication (creation or subsequent login), it updates the name, e-mail and
  dozens of custom profile fields from the SUAP data.
* Persists the changes via ``update_user_record()`` (inherited from ``auth_oauth2\auth``).
* If a photo URL is available, downloads and applies it via ``update_picture()``.

Step 5 — completing the login
--------------------------------

``complete_user_login($usuario)`` authenticates the Moodle session. Then,
``resolve_next_after_login()`` reads and clears ``$SESSION->next_after_next`` (set in step 1)
to determine the redirect target, falling back to the site root if nothing was saved.

Logout
------

``postlogout_hook()`` intercepts the logout of users with ``auth == 'suap'`` and redirects to
``/auth/suap/logout.php``, which shows a confirmation page: the user chooses between also
ending the SUAP session (``logout_url``) or staying signed in to Moodle.
``\core\session\manager::init_empty_session()`` is called before showing the page, to
invalidate the local Moodle session right away.
