# HireMe Recruitment Marketplace Design

## Context

HireMe va fi o platforma de recrutare adresata atat candidatilor care cauta joburi, cat si angajatorilor care vor sa gaseasca si sa gestioneze candidati. Viziunea de produs este un marketplace complet de recrutare, cu accent pe relatia dintre candidat si angajator: aplicari, shortlist, statusuri si mesagerie.

Platforma va fi gandita pentru publicare pe Hostinger Cloud Startup. Stack-ul ales este Laravel + MySQL, pentru ca ofera un echilibru bun intre functionalitate, viteza de dezvoltare, administrare usoara si compatibilitate cu hosting-ul disponibil.

## Scope Pentru Prima Versiune

Prima versiune va include nucleul necesar pentru Faza 2, adica relatia si selectia dintre candidat si angajator, dar fara monetizare sau analytics avansat.

Functionalitati incluse:

- inregistrare si autentificare pentru candidati si angajatori
- profil candidat cu date personale, rezumat profesional, experienta, competente si upload CV
- profil companie cu nume, descriere, logo, website si locatie
- creare, editare si publicare joburi de catre angajatori
- lista publica de joburi cu cautare si filtre simple
- pagina detaliu job cu aplicare
- aplicare la job cu profil/CV si mesaj scurt
- dashboard candidat cu aplicari, statusuri si conversatii
- dashboard angajator cu joburi, aplicari, shortlist, statusuri si conversatii
- mesagerie candidat-angajator legata de o aplicare
- panou admin minimal pentru utilizatori, companii si joburi
- notificari prin email pentru aplicare noua, mesaj nou si schimbare de status

Functionalitati excluse din prima versiune:

- plati online
- abonamente pentru angajatori
- joburi promovate
- analytics avansat
- matching automat cu AI
- aplicatii mobile native

## Arhitectura

Platforma va fi un monolith Laravel cu MySQL. Aceasta alegere pastreaza deployment-ul simplu pe Hostinger si permite dezvoltarea rapida a modulelor de business fara a introduce un backend separat sau un frontend SPA complet.

Module principale:

- Public Site: homepage, listare joburi, pagina detaliu job, pagini companie, autentificare
- Candidate Portal: profil candidat, CV-uri, aplicari, statusuri, mesaje
- Employer Portal: profil companie, joburi, candidati, shortlist, mesaje
- Admin Panel: moderare utilizatori, companii si joburi
- Applications: fluxul central prin care un candidat aplica la un job
- Messaging: conversatii intre candidat si angajator, legate de aplicari
- Notifications: emailuri si notificari in aplicatie
- Storage: CV-uri, logo-uri de companii si fisiere atasate

Laravel va servi paginile prin Blade si Vite, cu componente frontend reutilizabile unde este util. Pentru prima versiune nu este necesara o aplicatie React/Vue separata.

## Roluri Si Fluxuri

Platforma va avea trei roluri principale.

### Candidat

Candidatul isi creeaza cont, completeaza profilul profesional, incarca CV, seteaza preferinte, cauta joburi, aplica, urmareste statusul aplicarii si comunica cu angajatorul.

### Angajator

Angajatorul isi creeaza cont, completeaza profilul companiei, publica joburi, vede aplicari, salveaza candidati in shortlist, schimba statusuri si initiaza conversatii cu candidatii.

### Admin

Adminul gestioneaza utilizatori, companii si joburi. Poate aproba, bloca sau edita continut problematic. Adminul nu trebuie sa aiba un dashboard complex in prima versiune, ci unul utilitar si clar.

Flux central:

```text
Job publicat -> Candidat aplica -> Angajator verifica -> Shortlist/status -> Mesagerie -> Decizie
```

Statusuri initiale pentru aplicari:

- trimisa
- vizualizata
- shortlist
- interviu
- respinsa
- acceptata

## Model De Date

Tabele principale:

- `users`: conturi, email, parola hash-uita, rol si stare cont
- `candidate_profiles`: detalii candidat, locatie, telefon, rezumat, experienta si competente
- `companies`: profil companie, owner, nume, descriere, logo, website si locatie
- `jobs`: joburi publicate, companie, titlu, descriere, locatie, tip contract, nivel experienta, salariu optional si stare publicare
- `applications`: legatura candidat-job, mesaj, CV folosit, status si timestamps relevante
- `shortlists`: candidati salvati de angajatori pentru joburi sau companie
- `conversations`: conversatii legate de o aplicare
- `messages`: mesaje din conversatii, expeditor, continut si stare citire
- `notifications`: notificari in aplicatie si emailuri trimise prin mecanismul standard Laravel Notifications

Relatii cheie:

- un `user` candidat are un `candidate_profile`
- un `user` angajator poate detine una sau mai multe `companies`
- o `company` are mai multe `jobs`
- un `job` are mai multe `applications`
- o `application` apartine unui candidat si unui job
- o `conversation` este legata de o `application`
- o `conversation` are mai multe `messages`

## Interfata Si Experienta

Interfata trebuie sa fie profesionala, clara si orientata pe actiune. Nu va fi o pagina decorativa de prezentare, ci o platforma functionala care arata imediat cele doua drumuri:

- Caut un job
- Angajez oameni

Pagini principale:

- Homepage cu cautare joburi, categorii si actiuni pentru ambele audiente
- Lista joburi cu filtre pentru locatie, remote/hibrid/on-site, domeniu, nivel experienta si tip contract
- Detaliu job cu aplicare clara
- Dashboard candidat axat pe aplicari si conversatii
- Dashboard angajator axat pe pipeline, aplicari noi, shortlist, interviuri si mesaje
- Admin minimal, dens si utilitar

Design-ul va evita un landing page generic. Prioritatea este increderea, lizibilitatea si viteza cu care utilizatorii ajung la actiunea potrivita.

## Securitate Si Validare

Masuri initiale:

- parole hash-uite prin mecanismele standard Laravel
- validare server-side pentru toate formularele
- autorizare pe roluri pentru dashboard-uri
- restrictii la upload CV: PDF, DOC si DOCX
- limite de marime pentru fisiere uploadate
- rate limiting pe login si formulare sensibile
- protectie CSRF implicita Laravel
- moderare admin pentru joburi si companii
- email verification obligatoriu inainte ca un candidat sa aplice sau un angajator sa publice joburi

Datele sensibile, cum sunt CV-urile si datele de contact, vor fi accesibile doar utilizatorilor autorizati conform fluxului platformei.

## Hosting Si Deployment

Tinta de hosting este Hostinger Cloud Startup.

Decizii de deployment:

- Laravel + MySQL
- frontend build cu Vite
- storage local Laravel pentru CV-uri si logo-uri
- SMTP configurat prin `.env`
- configurare `.env` separata pentru local si productie
- deployment preferat prin Git/SSH; upload controlat va fi folosit doar daca accesul Git/SSH nu este disponibil in contul Hostinger
- cache Laravel pentru config, route si view in productie

Aplicatia nu va depinde de un proces Node permanent pentru prima versiune. Node va fi folosit doar pentru build-ul frontend prin Vite.

## Testare Si Criterii De Acceptare

Fluxuri care trebuie verificate inainte de lansare:

- un candidat isi poate crea cont si completa profilul
- un candidat poate incarca un CV valid
- un angajator isi poate crea cont si completa profilul companiei
- un angajator poate publica un job
- un candidat poate cauta si filtra joburi
- un candidat poate aplica la un job
- un angajator vede aplicarea in dashboard
- un angajator poate schimba statusul aplicarii
- un angajator poate salva candidatul in shortlist
- candidatul vede statusul actualizat
- candidatul si angajatorul pot trimite mesaje intr-o conversatie legata de aplicare
- adminul poate vedea si gestiona utilizatori, companii si joburi
- notificarile email sunt trimise pentru evenimentele stabilite
- interfata este utilizabila pe mobil si desktop
- aplicatia poate fi configurata si rulata intr-un mediu compatibil Hostinger

## Decizii Confirmate

- Produsul este un marketplace complet de recrutare, nu doar un job board.
- Prima versiune include conturi separate pentru candidat si angajator.
- Accentul principal este pe aplicari, shortlist, statusuri si mesagerie.
- Stack-ul este Laravel + MySQL.
- Hostinger Cloud Startup este mediul tinta de hosting.
- Platile, abonamentele, joburile promovate, analytics avansat si AI matching raman pentru faze viitoare.
