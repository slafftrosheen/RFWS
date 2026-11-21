-- Reclame Fabriek client portal · PostgreSQL schema v1. The site creates these tables itself on first connection;
-- this file is for reference, or to run once in the Supabase SQL editor.
--
-- PostgreSQL database dump
--

-- Dumped from database version 16.15 (Ubuntu 16.15-0ubuntu0.24.04.1)
-- Dumped by pg_dump version 16.15 (Ubuntu 16.15-0ubuntu0.24.04.1)

--
-- Name: rf_clients; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rf_clients (
    id bigint NOT NULL,
    email text NOT NULL,
    pass_hash text,
    name text DEFAULT ''::text NOT NULL,
    company text DEFAULT ''::text NOT NULL,
    vat text DEFAULT ''::text NOT NULL,
    phone text DEFAULT ''::text NOT NULL,
    country text DEFAULT ''::text NOT NULL,
    address text DEFAULT ''::text NOT NULL,
    lang character varying(2) DEFAULT 'en'::character varying NOT NULL,
    status text DEFAULT 'pending'::text NOT NULL,
    email_verified_at timestamp with time zone,
    notify boolean DEFAULT true NOT NULL,
    staff_note text DEFAULT ''::text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    last_login_at timestamp with time zone,
    CONSTRAINT rf_clients_status_check CHECK ((status = ANY (ARRAY['pending'::text, 'active'::text, 'blocked'::text])))
);

--
-- Name: rf_clients_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.rf_clients_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

--
-- Name: rf_clients_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.rf_clients_id_seq OWNED BY public.rf_clients.id;

--
-- Name: rf_counters; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rf_counters (
    k text NOT NULL,
    n integer NOT NULL
);

--
-- Name: rf_events; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rf_events (
    id bigint NOT NULL,
    request_id bigint NOT NULL,
    kind text NOT NULL,
    data jsonb DEFAULT '{}'::jsonb NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);

--
-- Name: rf_events_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.rf_events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

--
-- Name: rf_events_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.rf_events_id_seq OWNED BY public.rf_events.id;

--
-- Name: rf_files; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rf_files (
    id bigint NOT NULL,
    request_id bigint NOT NULL,
    message_id bigint,
    uploader_kind text NOT NULL,
    original_name text NOT NULL,
    stored_name text NOT NULL,
    mime text DEFAULT ''::text NOT NULL,
    size bigint DEFAULT 0 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);

--
-- Name: rf_files_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.rf_files_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

--
-- Name: rf_files_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.rf_files_id_seq OWNED BY public.rf_files.id;

--
-- Name: rf_messages; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rf_messages (
    id bigint NOT NULL,
    request_id bigint NOT NULL,
    author_kind text NOT NULL,
    author_name text DEFAULT ''::text NOT NULL,
    body text DEFAULT ''::text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT rf_messages_author_kind_check CHECK ((author_kind = ANY (ARRAY['client'::text, 'staff'::text, 'system'::text])))
);

--
-- Name: rf_messages_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.rf_messages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

--
-- Name: rf_messages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.rf_messages_id_seq OWNED BY public.rf_messages.id;

--
-- Name: rf_meta; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rf_meta (
    k text NOT NULL,
    v text NOT NULL
);

--
-- Name: rf_quote_lines; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rf_quote_lines (
    id bigint NOT NULL,
    quote_id bigint NOT NULL,
    "position" integer DEFAULT 0 NOT NULL,
    profile_slug text DEFAULT ''::text NOT NULL,
    description text NOT NULL,
    qty numeric(12,2) DEFAULT 1 NOT NULL,
    unit text DEFAULT 'pcs'::text NOT NULL,
    unit_price numeric(12,2) DEFAULT 0 NOT NULL
);

--
-- Name: rf_quote_lines_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.rf_quote_lines_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

--
-- Name: rf_quote_lines_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.rf_quote_lines_id_seq OWNED BY public.rf_quote_lines.id;

--
-- Name: rf_quotes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rf_quotes (
    id bigint NOT NULL,
    request_id bigint NOT NULL,
    no text NOT NULL,
    version integer DEFAULT 1 NOT NULL,
    status text DEFAULT 'draft'::text NOT NULL,
    currency text DEFAULT 'EUR'::text NOT NULL,
    vat_rate numeric(5,2) DEFAULT 21 NOT NULL,
    valid_until date,
    lead_time text DEFAULT ''::text NOT NULL,
    intro text DEFAULT ''::text NOT NULL,
    terms text DEFAULT ''::text NOT NULL,
    created_by text DEFAULT ''::text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    sent_at timestamp with time zone,
    decided_at timestamp with time zone,
    decided_name text DEFAULT ''::text NOT NULL,
    decision_note text DEFAULT ''::text NOT NULL,
    decided_ip text DEFAULT ''::text NOT NULL,
    CONSTRAINT rf_quotes_status_check CHECK ((status = ANY (ARRAY['draft'::text, 'sent'::text, 'accepted'::text, 'declined'::text, 'expired'::text, 'superseded'::text])))
);

--
-- Name: rf_quotes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.rf_quotes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

--
-- Name: rf_quotes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.rf_quotes_id_seq OWNED BY public.rf_quotes.id;

--
-- Name: rf_request_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rf_request_items (
    id bigint NOT NULL,
    request_id bigint NOT NULL,
    "position" integer DEFAULT 0 NOT NULL,
    profile_slug text DEFAULT ''::text NOT NULL,
    profile_title text DEFAULT ''::text NOT NULL,
    light text DEFAULT ''::text NOT NULL,
    content text DEFAULT ''::text NOT NULL,
    height_mm integer,
    depth text DEFAULT ''::text NOT NULL,
    qty integer DEFAULT 1 NOT NULL,
    face_colour text DEFAULT ''::text NOT NULL,
    side_colour text DEFAULT ''::text NOT NULL,
    mounting text DEFAULT ''::text NOT NULL,
    notes text DEFAULT ''::text NOT NULL,
    CONSTRAINT rf_request_items_mounting_check CHECK ((mounting = ANY (ARRAY[''::text, 'no_frame'::text, 'on_frame'::text])))
);

--
-- Name: rf_request_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.rf_request_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

--
-- Name: rf_request_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.rf_request_items_id_seq OWNED BY public.rf_request_items.id;

--
-- Name: rf_requests; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rf_requests (
    id bigint NOT NULL,
    no text NOT NULL,
    client_id bigint NOT NULL,
    title text NOT NULL,
    status text DEFAULT 'new'::text NOT NULL,
    site_address text DEFAULT ''::text NOT NULL,
    deadline date,
    notes text DEFAULT ''::text NOT NULL,
    source text DEFAULT 'portal'::text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    client_seen_at timestamp with time zone DEFAULT now() NOT NULL,
    staff_seen_at timestamp with time zone,
    CONSTRAINT rf_requests_status_check CHECK ((status = ANY (ARRAY['new'::text, 'review'::text, 'quoted'::text, 'accepted'::text, 'production'::text, 'ready'::text, 'installed'::text, 'closed'::text, 'cancelled'::text])))
);

--
-- Name: rf_requests_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.rf_requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

--
-- Name: rf_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.rf_requests_id_seq OWNED BY public.rf_requests.id;

--
-- Name: rf_throttle; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rf_throttle (
    k text NOT NULL,
    n integer NOT NULL,
    until timestamp with time zone NOT NULL
);

--
-- Name: rf_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rf_tokens (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    kind text NOT NULL,
    token_hash text NOT NULL,
    expires_at timestamp with time zone NOT NULL,
    used_at timestamp with time zone,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT rf_tokens_kind_check CHECK ((kind = ANY (ARRAY['verify'::text, 'reset'::text, 'invite'::text])))
);

--
-- Name: rf_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.rf_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

--
-- Name: rf_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.rf_tokens_id_seq OWNED BY public.rf_tokens.id;

--
-- Name: rf_clients id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_clients ALTER COLUMN id SET DEFAULT nextval('public.rf_clients_id_seq'::regclass);

--
-- Name: rf_events id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_events ALTER COLUMN id SET DEFAULT nextval('public.rf_events_id_seq'::regclass);

--
-- Name: rf_files id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_files ALTER COLUMN id SET DEFAULT nextval('public.rf_files_id_seq'::regclass);

--
-- Name: rf_messages id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_messages ALTER COLUMN id SET DEFAULT nextval('public.rf_messages_id_seq'::regclass);

--
-- Name: rf_quote_lines id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_quote_lines ALTER COLUMN id SET DEFAULT nextval('public.rf_quote_lines_id_seq'::regclass);

--
-- Name: rf_quotes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_quotes ALTER COLUMN id SET DEFAULT nextval('public.rf_quotes_id_seq'::regclass);

--
-- Name: rf_request_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_request_items ALTER COLUMN id SET DEFAULT nextval('public.rf_request_items_id_seq'::regclass);

--
-- Name: rf_requests id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_requests ALTER COLUMN id SET DEFAULT nextval('public.rf_requests_id_seq'::regclass);

--
-- Name: rf_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_tokens ALTER COLUMN id SET DEFAULT nextval('public.rf_tokens_id_seq'::regclass);

--
-- Name: rf_clients rf_clients_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_clients
    ADD CONSTRAINT rf_clients_email_key UNIQUE (email);

--
-- Name: rf_clients rf_clients_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_clients
    ADD CONSTRAINT rf_clients_pkey PRIMARY KEY (id);

--
-- Name: rf_counters rf_counters_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_counters
    ADD CONSTRAINT rf_counters_pkey PRIMARY KEY (k);

--
-- Name: rf_events rf_events_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_events
    ADD CONSTRAINT rf_events_pkey PRIMARY KEY (id);

--
-- Name: rf_files rf_files_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_files
    ADD CONSTRAINT rf_files_pkey PRIMARY KEY (id);

--
-- Name: rf_files rf_files_stored_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_files
    ADD CONSTRAINT rf_files_stored_name_key UNIQUE (stored_name);

--
-- Name: rf_messages rf_messages_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_messages
    ADD CONSTRAINT rf_messages_pkey PRIMARY KEY (id);

--
-- Name: rf_meta rf_meta_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_meta
    ADD CONSTRAINT rf_meta_pkey PRIMARY KEY (k);

--
-- Name: rf_quote_lines rf_quote_lines_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_quote_lines
    ADD CONSTRAINT rf_quote_lines_pkey PRIMARY KEY (id);

--
-- Name: rf_quotes rf_quotes_no_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_quotes
    ADD CONSTRAINT rf_quotes_no_key UNIQUE (no);

--
-- Name: rf_quotes rf_quotes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_quotes
    ADD CONSTRAINT rf_quotes_pkey PRIMARY KEY (id);

--
-- Name: rf_request_items rf_request_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_request_items
    ADD CONSTRAINT rf_request_items_pkey PRIMARY KEY (id);

--
-- Name: rf_requests rf_requests_no_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_requests
    ADD CONSTRAINT rf_requests_no_key UNIQUE (no);

--
-- Name: rf_requests rf_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_requests
    ADD CONSTRAINT rf_requests_pkey PRIMARY KEY (id);

--
-- Name: rf_throttle rf_throttle_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_throttle
    ADD CONSTRAINT rf_throttle_pkey PRIMARY KEY (k);

--
-- Name: rf_tokens rf_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_tokens
    ADD CONSTRAINT rf_tokens_pkey PRIMARY KEY (id);

--
-- Name: rf_tokens rf_tokens_token_hash_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_tokens
    ADD CONSTRAINT rf_tokens_token_hash_key UNIQUE (token_hash);

--
-- Name: rf_messages_req; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX rf_messages_req ON public.rf_messages USING btree (request_id, created_at);

--
-- Name: rf_requests_client; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX rf_requests_client ON public.rf_requests USING btree (client_id, updated_at DESC);

--
-- Name: rf_events rf_events_request_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_events
    ADD CONSTRAINT rf_events_request_id_fkey FOREIGN KEY (request_id) REFERENCES public.rf_requests(id) ON DELETE CASCADE;

--
-- Name: rf_files rf_files_message_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_files
    ADD CONSTRAINT rf_files_message_id_fkey FOREIGN KEY (message_id) REFERENCES public.rf_messages(id) ON DELETE SET NULL;

--
-- Name: rf_files rf_files_request_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_files
    ADD CONSTRAINT rf_files_request_id_fkey FOREIGN KEY (request_id) REFERENCES public.rf_requests(id) ON DELETE CASCADE;

--
-- Name: rf_messages rf_messages_request_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_messages
    ADD CONSTRAINT rf_messages_request_id_fkey FOREIGN KEY (request_id) REFERENCES public.rf_requests(id) ON DELETE CASCADE;

--
-- Name: rf_quote_lines rf_quote_lines_quote_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_quote_lines
    ADD CONSTRAINT rf_quote_lines_quote_id_fkey FOREIGN KEY (quote_id) REFERENCES public.rf_quotes(id) ON DELETE CASCADE;

--
-- Name: rf_quotes rf_quotes_request_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_quotes
    ADD CONSTRAINT rf_quotes_request_id_fkey FOREIGN KEY (request_id) REFERENCES public.rf_requests(id) ON DELETE CASCADE;

--
-- Name: rf_request_items rf_request_items_request_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_request_items
    ADD CONSTRAINT rf_request_items_request_id_fkey FOREIGN KEY (request_id) REFERENCES public.rf_requests(id) ON DELETE CASCADE;

--
-- Name: rf_requests rf_requests_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_requests
    ADD CONSTRAINT rf_requests_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.rf_clients(id) ON DELETE CASCADE;

--
-- Name: rf_tokens rf_tokens_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rf_tokens
    ADD CONSTRAINT rf_tokens_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.rf_clients(id) ON DELETE CASCADE;

--
-- PostgreSQL database dump complete
--
