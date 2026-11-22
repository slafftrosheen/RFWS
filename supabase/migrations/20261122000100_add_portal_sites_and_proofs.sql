CREATE TABLE IF NOT EXISTS public.rf_sites (
  id bigserial PRIMARY KEY, client_id bigint NOT NULL REFERENCES public.rf_clients(id) ON DELETE CASCADE,
  label text NOT NULL, address text NOT NULL DEFAULT '', contact text NOT NULL DEFAULT '', phone text NOT NULL DEFAULT '', notes text NOT NULL DEFAULT '',
  created_at timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS rf_sites_client ON public.rf_sites (client_id);

CREATE TABLE IF NOT EXISTS public.rf_proofs (
  id bigserial PRIMARY KEY, request_id bigint NOT NULL REFERENCES public.rf_requests(id) ON DELETE CASCADE,
  file_id bigint NOT NULL REFERENCES public.rf_files(id) ON DELETE CASCADE, version integer NOT NULL DEFAULT 1, note text NOT NULL DEFAULT '',
  status text NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'changes', 'superseded')),
  created_by text NOT NULL DEFAULT '', created_at timestamptz NOT NULL DEFAULT now(),
  decided_at timestamptz, decided_name text NOT NULL DEFAULT '', decision_note text NOT NULL DEFAULT '', decided_ip text NOT NULL DEFAULT ''
);
CREATE INDEX IF NOT EXISTS rf_proofs_req ON public.rf_proofs (request_id, version DESC);

CREATE TABLE IF NOT EXISTS public.rf_feedback (
  request_id bigint PRIMARY KEY REFERENCES public.rf_requests(id) ON DELETE CASCADE,
  rating integer NOT NULL CHECK (rating BETWEEN 1 AND 5), comment text NOT NULL DEFAULT '', created_at timestamptz NOT NULL DEFAULT now()
);
ALTER TABLE public.rf_requests ADD COLUMN IF NOT EXISTS site_id bigint REFERENCES public.rf_sites(id) ON DELETE SET NULL;
ALTER TABLE public.rf_clients ADD COLUMN IF NOT EXISTS activity_seen_at timestamptz NOT NULL DEFAULT now();
CREATE INDEX IF NOT EXISTS rf_events_req ON public.rf_events (request_id, created_at DESC);

ALTER TABLE public.rf_sites ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.rf_proofs ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.rf_feedback ENABLE ROW LEVEL SECURITY;

UPDATE public.rf_meta SET v = '2' WHERE k = 'schema' AND v::integer < 2;
