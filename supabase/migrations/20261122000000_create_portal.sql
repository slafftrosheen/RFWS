-- Portal schema v1, matching db_migrate() in site/app/db.php.
CREATE TABLE IF NOT EXISTS public.rf_meta (k text PRIMARY KEY, v text NOT NULL);
CREATE TABLE IF NOT EXISTS public.rf_counters (k text PRIMARY KEY, n integer NOT NULL);
CREATE TABLE IF NOT EXISTS public.rf_clients (
  id bigserial PRIMARY KEY,
  email text NOT NULL UNIQUE,
  pass_hash text,
  name text NOT NULL DEFAULT '', company text NOT NULL DEFAULT '', vat text NOT NULL DEFAULT '', phone text NOT NULL DEFAULT '',
  country text NOT NULL DEFAULT '', address text NOT NULL DEFAULT '',
  lang varchar(2) NOT NULL DEFAULT 'en',
  status text NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'active', 'blocked')),
  email_verified_at timestamptz, notify boolean NOT NULL DEFAULT true,
  staff_note text NOT NULL DEFAULT '',
  created_at timestamptz NOT NULL DEFAULT now(), last_login_at timestamptz
);
CREATE TABLE IF NOT EXISTS public.rf_tokens (
  id bigserial PRIMARY KEY, client_id bigint NOT NULL REFERENCES public.rf_clients(id) ON DELETE CASCADE,
  kind text NOT NULL CHECK (kind IN ('verify', 'reset', 'invite')), token_hash text NOT NULL UNIQUE,
  expires_at timestamptz NOT NULL, used_at timestamptz, created_at timestamptz NOT NULL DEFAULT now()
);
CREATE TABLE IF NOT EXISTS public.rf_requests (
  id bigserial PRIMARY KEY, no text NOT NULL UNIQUE, client_id bigint NOT NULL REFERENCES public.rf_clients(id) ON DELETE CASCADE,
  title text NOT NULL,
  status text NOT NULL DEFAULT 'new' CHECK (status IN ('new', 'review', 'quoted', 'accepted', 'production', 'ready', 'installed', 'closed', 'cancelled')),
  site_address text NOT NULL DEFAULT '', deadline date, notes text NOT NULL DEFAULT '', source text NOT NULL DEFAULT 'portal',
  created_at timestamptz NOT NULL DEFAULT now(), updated_at timestamptz NOT NULL DEFAULT now(),
  client_seen_at timestamptz NOT NULL DEFAULT now(), staff_seen_at timestamptz
);
CREATE INDEX IF NOT EXISTS rf_requests_client ON public.rf_requests (client_id, updated_at DESC);
CREATE TABLE IF NOT EXISTS public.rf_request_items (
  id bigserial PRIMARY KEY, request_id bigint NOT NULL REFERENCES public.rf_requests(id) ON DELETE CASCADE, position integer NOT NULL DEFAULT 0,
  profile_slug text NOT NULL DEFAULT '', profile_title text NOT NULL DEFAULT '', light text NOT NULL DEFAULT '',
  content text NOT NULL DEFAULT '', height_mm integer, depth text NOT NULL DEFAULT '', qty integer NOT NULL DEFAULT 1,
  face_colour text NOT NULL DEFAULT '', side_colour text NOT NULL DEFAULT '',
  mounting text NOT NULL DEFAULT '' CHECK (mounting IN ('', 'no_frame', 'on_frame')), notes text NOT NULL DEFAULT ''
);
CREATE TABLE IF NOT EXISTS public.rf_messages (
  id bigserial PRIMARY KEY, request_id bigint NOT NULL REFERENCES public.rf_requests(id) ON DELETE CASCADE,
  author_kind text NOT NULL CHECK (author_kind IN ('client', 'staff', 'system')), author_name text NOT NULL DEFAULT '',
  body text NOT NULL DEFAULT '', created_at timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS rf_messages_req ON public.rf_messages (request_id, created_at);
CREATE TABLE IF NOT EXISTS public.rf_files (
  id bigserial PRIMARY KEY, request_id bigint NOT NULL REFERENCES public.rf_requests(id) ON DELETE CASCADE,
  message_id bigint REFERENCES public.rf_messages(id) ON DELETE SET NULL, uploader_kind text NOT NULL,
  original_name text NOT NULL, stored_name text NOT NULL UNIQUE, mime text NOT NULL DEFAULT '', size bigint NOT NULL DEFAULT 0,
  created_at timestamptz NOT NULL DEFAULT now()
);
CREATE TABLE IF NOT EXISTS public.rf_quotes (
  id bigserial PRIMARY KEY, request_id bigint NOT NULL REFERENCES public.rf_requests(id) ON DELETE CASCADE,
  no text NOT NULL UNIQUE, version integer NOT NULL DEFAULT 1,
  status text NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'sent', 'accepted', 'declined', 'expired', 'superseded')),
  currency text NOT NULL DEFAULT 'EUR', vat_rate numeric(5,2) NOT NULL DEFAULT 21, valid_until date,
  lead_time text NOT NULL DEFAULT '', intro text NOT NULL DEFAULT '', terms text NOT NULL DEFAULT '',
  created_by text NOT NULL DEFAULT '', created_at timestamptz NOT NULL DEFAULT now(), sent_at timestamptz,
  decided_at timestamptz, decided_name text NOT NULL DEFAULT '', decision_note text NOT NULL DEFAULT '', decided_ip text NOT NULL DEFAULT ''
);
CREATE TABLE IF NOT EXISTS public.rf_quote_lines (
  id bigserial PRIMARY KEY, quote_id bigint NOT NULL REFERENCES public.rf_quotes(id) ON DELETE CASCADE, position integer NOT NULL DEFAULT 0,
  profile_slug text NOT NULL DEFAULT '', description text NOT NULL, qty numeric(12,2) NOT NULL DEFAULT 1,
  unit text NOT NULL DEFAULT 'pcs', unit_price numeric(12,2) NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS public.rf_events (
  id bigserial PRIMARY KEY, request_id bigint NOT NULL REFERENCES public.rf_requests(id) ON DELETE CASCADE,
  kind text NOT NULL, data jsonb NOT NULL DEFAULT '{}', created_at timestamptz NOT NULL DEFAULT now()
);
CREATE TABLE IF NOT EXISTS public.rf_throttle (k text PRIMARY KEY, n integer NOT NULL, until timestamptz NOT NULL);

-- PHP owns portal access. Supabase Data API roles have no portal policies.
ALTER TABLE public.rf_meta ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.rf_counters ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.rf_clients ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.rf_tokens ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.rf_requests ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.rf_request_items ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.rf_messages ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.rf_files ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.rf_quotes ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.rf_quote_lines ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.rf_events ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.rf_throttle ENABLE ROW LEVEL SECURITY;

INSERT INTO public.rf_meta (k, v) VALUES ('schema', '1') ON CONFLICT (k) DO NOTHING;
