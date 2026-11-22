ALTER TABLE public.rf_requests ADD COLUMN IF NOT EXISTS assignee text NOT NULL DEFAULT '';
ALTER TABLE public.rf_requests ADD COLUMN IF NOT EXISTS follow_up date;
ALTER TABLE public.rf_requests ADD COLUMN IF NOT EXISTS install_at timestamptz;
ALTER TABLE public.rf_requests ADD COLUMN IF NOT EXISTS install_hours numeric(4,1) NOT NULL DEFAULT 4;
ALTER TABLE public.rf_requests ADD COLUMN IF NOT EXISTS install_state text NOT NULL DEFAULT '';
ALTER TABLE public.rf_requests ADD COLUMN IF NOT EXISTS install_note text NOT NULL DEFAULT '';
ALTER TABLE public.rf_requests ADD COLUMN IF NOT EXISTS install_crew text NOT NULL DEFAULT '';
ALTER TABLE public.rf_clients ADD COLUMN IF NOT EXISTS cc_emails text NOT NULL DEFAULT '';

CREATE TABLE IF NOT EXISTS public.rf_notes (
  id bigserial PRIMARY KEY, request_id bigint NOT NULL REFERENCES public.rf_requests(id) ON DELETE CASCADE,
  author text NOT NULL DEFAULT '', body text NOT NULL, created_at timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS rf_notes_req ON public.rf_notes (request_id, created_at DESC);
CREATE INDEX IF NOT EXISTS rf_requests_install ON public.rf_requests (install_at) WHERE install_at IS NOT NULL;
ALTER TABLE public.rf_notes ENABLE ROW LEVEL SECURITY;

UPDATE public.rf_meta SET v = '3' WHERE k = 'schema' AND v::integer < 3;
