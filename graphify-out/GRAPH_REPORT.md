# Graph Report - app  (2026-10-05)

## Corpus Check
- 271 files · ~53,350 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1890 nodes · 4772 edges · 115 communities (81 shown, 34 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 48 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- Audit Finding Pages
- OAM Practice Import
- Branch & Auditable Models
- AI Assistant Chat
- Client Blacklist Models
- Client Model
- Filament Table Actions
- Document Import Actions
- Dynamic Group Export
- Audit Finding Form & Infolists
- Form Field Components
- Morph Selects
- Community 12
- Community 13
- Community 14
- Community 15
- Community 16
- Community 17
- Community 18
- Community 19
- Community 20
- Community 21
- Community 22
- Community 23
- Community 24
- Community 25
- Community 26
- Community 27
- Community 28
- Community 29
- Community 30
- Community 31
- Community 32
- Community 33
- Community 34
- Community 35
- Community 36
- Community 37
- Community 38
- Community 39
- Community 40
- Community 41
- Community 42
- Community 43
- Community 44
- Community 45
- Community 46
- Community 47
- Community 48
- Community 49
- Community 50
- Community 51
- Community 52
- Community 53
- Community 54
- Community 55
- Community 56
- Community 57
- Community 58
- Community 59
- Community 60
- Community 61
- Community 62
- Community 63
- Community 64
- Community 65
- Community 66
- Community 67
- Community 68
- Community 69
- Community 70
- Community 71
- Community 72
- Community 74
- Community 75
- Community 76
- Community 78
- Community 79
- Community 80
- Community 81
- Community 82
- Community 83
- Community 84
- Community 85
- Community 86
- Community 87
- Community 88
- Community 89
- Community 90
- Community 91
- Community 92
- Community 93
- Community 94
- Community 95
- Community 96
- Community 97
- Community 98
- Community 99
- Community 100
- Community 101
- Community 102
- Community 103
- Community 104
- Community 105
- Community 106
- Community 108
- Community 109
- Community 110
- Community 111
- Community 112
- Community 113

## God Nodes (most connected - your core abstractions)
1. `OamSemester` - 64 edges
2. `Document` - 56 edges
3. `HasPlanAccess` - 54 edges
4. `Company` - 38 edges
5. `Clienti` - 34 edges
6. `DynamicGroupExport` - 33 edges
7. `Fornitore` - 33 edges
8. `Pratica` - 31 edges
9. `Task` - 31 edges
10. `Resource` - 30 edges

## Surprising Connections (you probably didn't know these)
- `{closure#1}()` --calls--> `Task`  [INFERRED]
  Filament/Resources/Companies/Pages/EditCompany.php → Models/Task.php
- `{closure#1}()` --references--> `DocumentSchedule`  [EXTRACTED]
  Console/Commands/CheckExpiredDocumentsCommand.php → Models/DocumentSchedule.php
- `{closure#2}()` --references--> `DocumentSchedule`  [EXTRACTED]
  Console/Commands/CheckExpiredDocumentsCommand.php → Models/DocumentSchedule.php
- `{closure#1}()` --calls--> `DocumentSchedule`  [EXTRACTED]
  Console/Commands/SyncDocumentSchedules.php → Models/DocumentSchedule.php
- `{closure#1}()` --calls--> `OamSemester`  [EXTRACTED]
  Filament/Actions/ImportOamAction.php → ValueObjects/OamSemester.php

## Import Cycles
- None detected.

## Communities (115 total, 34 thin omitted)

### Community 0 - "Audit Finding Pages"
Cohesion: 0.06
Nodes (18): EditAuditFinding, EditAudit, EditClienti, EditComplaintRegistry, EditDocument, EditDocumentType, EditEmailTemplate, EditEmployee (+10 more)

### Community 1 - "OAM Practice Import"
Cohesion: 0.07
Nodes (8): ImportPraticheOamCommand, {closure#9}(), Pratica, Provvigione, {closure#1}(), {closure#2}(), {closure#4}(), ImportPraticheService

### Community 2 - "Branch & Auditable Models"
Cohesion: 0.07
Nodes (7): Branch, {closure#1}(), ComplaintRegistry, MailAccount, Organization, TrainingRecord, Website

### Community 3 - "AI Assistant Chat"
Cohesion: 0.08
Nodes (6): AssistenteAi, {closure#2}(), AssistantEscalationMail, DocumentReminderMail, ProducerTrainingSummaryMail, ScadenziarioReportMail

### Community 4 - "Client Blacklist Models"
Cohesion: 0.07
Nodes (9): BlacklistClienteFornitore, ClientiOam, {closure#1}(), ClientRelation, ProvvigioniRule, RequisitoTipoFinanziamento, {closure#1}(), TaskDocumentType (+1 more)

### Community 5 - "Client Model"
Cohesion: 0.07
Nodes (5): Client, FornitoriRole, PraticaRequisito, Tipoprodotto, TipoprodottoSub

### Community 6 - "Filament Table Actions"
Cohesion: 0.07
Nodes (17): {closure#7}(), {closure#8}(), {closure#8}(), {closure#13}(), {closure#18}(), {closure#1}(), {closure#2}(), {closure#3}() (+9 more)

### Community 7 - "Document Import Actions"
Cohesion: 0.06
Nodes (14): {closure#1}(), {closure#11}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#8}(), {closure#9}(), {closure#12}() (+6 more)

### Community 8 - "Dynamic Group Export"
Cohesion: 0.12
Nodes (8): {closure#1}(), {closure#1}(), {closure#1}(), {closure#1}(), M510EconomicoOldSheet, {closure#1}(), {closure#1}(), {closure#1}()

### Community 9 - "Audit Finding Form & Infolists"
Cohesion: 0.10
Nodes (8): AuditFindingForm, EmployeeTypeInfolist, OamSemestraleForm, CreateResource, ResourceResource, ResourceForm, ResourceInfolist, UserInfolist

### Community 11 - "Morph Selects"
Cohesion: 0.11
Nodes (20): {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), {closure#1}() (+12 more)

### Community 13 - "Community 13"
Cohesion: 0.10
Nodes (5): M510MasterExport, OamSemestraleExport, M510AnagraficaSheet, M510PrudenzialeSheet, OamCompletoExport

### Community 15 - "Community 15"
Cohesion: 0.11
Nodes (8): Dashboard, ClientiResource, CreateClienti, ClientiForm, ClientisTable, OrganizationResource, CreateOrganization, OrganizationForm

### Community 16 - "Community 16"
Cohesion: 0.09
Nodes (3): Audit, {closure#1}(), OamSemester

### Community 17 - "Community 17"
Cohesion: 0.11
Nodes (6): CompaniesTable, {closure#1}(), {closure#2}(), {closure#3}(), ResourcesTable, UsersTable

### Community 18 - "Community 18"
Cohesion: 0.11
Nodes (8): AuditFindingsTable, {closure#5}(), {closure#6}(), {closure#7}(), EmployeeTypesTable, MailAccountsTable, OrganizationsTable, RemediationsTable

### Community 19 - "Community 19"
Cohesion: 0.09
Nodes (15): {closure#1}(), {closure#10}(), {closure#11}(), {closure#15}(), {closure#16}(), {closure#17}(), {closure#2}(), {closure#3}() (+7 more)

### Community 20 - "Community 20"
Cohesion: 0.09
Nodes (4): ClientType, PraticaDocumentRequest, PraticaStati, PraticaStatusHistory

### Community 21 - "Community 21"
Cohesion: 0.08
Nodes (4): AuditFinding, LogsComplianceActivity, Remediation, SuspiciousActivityReport

### Community 22 - "Community 22"
Cohesion: 0.12
Nodes (8): ListAuditFindings, ListCompanies, ListEmailTemplates, ListOamPratiches, ListRemediations, ListResources, ListSuspiciousActivityReports, ListUsers

### Community 23 - "Community 23"
Cohesion: 0.11
Nodes (6): AuditFindingResource, CreateAuditFinding, CreateRemediation, RemediationResource, RemediationForm, HasPlanAccess

### Community 24 - "Community 24"
Cohesion: 0.12
Nodes (8): DocumentTypeResource, CreateDocumentType, DocumentTypeForm, DocumentTypesTable, FornitoreResource, FornitoreForm, FornitoresTable, WebsitesRelationManager

### Community 25 - "Community 25"
Cohesion: 0.18
Nodes (17): {closure#1}(), {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#2}() (+9 more)

### Community 26 - "Community 26"
Cohesion: 0.17
Nodes (16): {closure#1}(), {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#2}(), {closure#3}() (+8 more)

### Community 28 - "Community 28"
Cohesion: 0.13
Nodes (6): DynamicGroupExport, AuditResource, CreateAudit, AuditForm, AuditsTable, DocumentsRelationManager

### Community 29 - "Community 29"
Cohesion: 0.18
Nodes (6): DocumentScheduleSyncApiController, ModelFieldsApiController, ModelFieldValueApiController, ProducerTrainingSummaryApiController, UserLookupApiController, Controller

### Community 32 - "Community 32"
Cohesion: 0.17
Nodes (5): {closure#1}(), {closure#1}(), {closure#1}(), {closure#2}(), {closure#2}()

### Community 33 - "Community 33"
Cohesion: 0.12
Nodes (7): {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#2}(), {closure#4}(), TableHelper

### Community 34 - "Community 34"
Cohesion: 0.12
Nodes (9): {closure#16}(), {closure#1}(), {closure#2}(), {closure#5}(), {closure#6}(), {closure#7}(), OamSemestrale, OamSemestrali (+1 more)

### Community 35 - "Community 35"
Cohesion: 0.16
Nodes (7): ImportOamAction, {closure#10}(), {closure#12}(), {closure#14}(), {closure#4}(), {closure#8}(), checkPiano()

### Community 36 - "Community 36"
Cohesion: 0.14
Nodes (5): {closure#5}(), FindingsRelationManager, CompanyRolesRelationManager, MailAccountRelationManager, HasRelationPlanAccess

### Community 37 - "Community 37"
Cohesion: 0.17
Nodes (8): {closure#13}(), {closure#15}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#9}(), ListOamSemestrales, DocumentDownloadController

### Community 38 - "Community 38"
Cohesion: 0.17
Nodes (5): {closure#1}(), Document, {closure#4}(), {closure#6}(), {closure#7}()

### Community 39 - "Community 39"
Cohesion: 0.15
Nodes (4): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}()

### Community 40 - "Community 40"
Cohesion: 0.18
Nodes (9): CheckExpiredDocumentsCommand, {closure#1}(), {closure#2}(), Severity, Alert, Ok, Regular, Warning (+1 more)

### Community 41 - "Community 41"
Cohesion: 0.17
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), ListAudits, CompanyRole

### Community 42 - "Community 42"
Cohesion: 0.12
Nodes (3): {closure#3}(), {closure#4}(), FormHelper

### Community 44 - "Community 44"
Cohesion: 0.50
Nodes (5): CompanyType, ALBERGO, CALL_CENTER, MEDIATORE, SOFTWARE_HOUSE

### Community 45 - "Community 45"
Cohesion: 0.16
Nodes (7): PlanType, Base, Full, Medium, resolvePianoAccess(), resolveUserEmployeeTypeIds(), EmployeeTypePermission

### Community 46 - "Community 46"
Cohesion: 0.19
Nodes (5): OamCodeResource, CreateOamCode, EditOamCode, OamCodeForm, OamCodesTable

### Community 47 - "Community 47"
Cohesion: 0.19
Nodes (5): StatusCheckCommand, {closure#1}(), SyncDocumentSchedules, SyncResourcesCommand, {closure#1}()

### Community 48 - "Community 48"
Cohesion: 0.24
Nodes (3): SendDocumentRemindersCommand, DocumentReminder, DocumentReminderService

### Community 49 - "Community 49"
Cohesion: 0.18
Nodes (7): ChatUsageRelationManager, {closure#1}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#8}()

### Community 50 - "Community 50"
Cohesion: 0.21
Nodes (6): {closure#2}(), {closure#18}(), {closure#3}(), {closure#1}(), {closure#2}(), OamCode

### Community 52 - "Community 52"
Cohesion: 0.49
Nodes (10): DocumentStatus, APPROVED, EXPIRED, NA, NOREADABLE, PENDING, PROVISIONAL, REJECTED (+2 more)

### Community 53 - "Community 53"
Cohesion: 0.19
Nodes (3): ListClientis, ListFornitores, ListTasks

### Community 54 - "Community 54"
Cohesion: 0.22
Nodes (4): DocumentResource, CreateDocument, DocumentForm, DocumentsTable

### Community 55 - "Community 55"
Cohesion: 0.19
Nodes (8): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), DocumentType

### Community 56 - "Community 56"
Cohesion: 0.19
Nodes (8): {closure#14}(), {closure#1}(), {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#8}(), OamSemestraleService

### Community 57 - "Community 57"
Cohesion: 0.22
Nodes (4): EmployeeTypeResource, CreateEmployeeType, ListEmployeeTypes, EmployeeTypeForm

### Community 58 - "Community 58"
Cohesion: 0.22
Nodes (4): CreateFornitore, MailAccountResource, CreateMailAccount, MailAccountForm

### Community 59 - "Community 59"
Cohesion: 0.21
Nodes (4): CreateSuspiciousActivityReport, SuspiciousActivityReportForm, SuspiciousActivityReportResource, SuspiciousActivityReportsTable

### Community 62 - "Community 62"
Cohesion: 0.20
Nodes (4): CompanyResource, CreateCompany, CompanyForm, BranchesRelationManager

### Community 63 - "Community 63"
Cohesion: 0.23
Nodes (4): ComplaintRegistryResource, CreateComplaintRegistry, ComplaintRegistryForm, ComplaintRegistriesTable

### Community 64 - "Community 64"
Cohesion: 0.23
Nodes (4): EmailTemplateResource, CreateEmailTemplate, EmailTemplateForm, EmailTemplatesTable

### Community 65 - "Community 65"
Cohesion: 0.23
Nodes (4): EmployeeResource, CreateEmployee, EmployeeForm, EmployeesTable

### Community 66 - "Community 66"
Cohesion: 0.23
Nodes (4): OamPraticheResource, CreateOamPratiche, OamPraticheForm, OamPratichesTable

### Community 67 - "Community 67"
Cohesion: 0.24
Nodes (4): OamSemestraleResource, CreateOamSemestrale, EditOamSemestrale, OamSemestralesTable

### Community 68 - "Community 68"
Cohesion: 0.23
Nodes (4): CreateTask, TaskForm, TasksTable, TaskResource

### Community 69 - "Community 69"
Cohesion: 0.27
Nodes (3): ViewEmployeeType, ViewResource, ViewUser

### Community 70 - "Community 70"
Cohesion: 0.18
Nodes (4): {closure#1}(), ExportOamAction, {closure#1}(), EditCompany

### Community 71 - "Community 71"
Cohesion: 0.22
Nodes (5): {closure#9}(), ChatMessage, {closure#1}(), {closure#2}(), {closure#3}()

### Community 72 - "Community 72"
Cohesion: 0.27
Nodes (8): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), OamPratiche

### Community 74 - "Community 74"
Cohesion: 0.24
Nodes (3): PraticaStato, {closure#1}(), {closure#2}()

### Community 75 - "Community 75"
Cohesion: 0.22
Nodes (6): EmailTemplate, {closure#1}(), {closure#2}(), {closure#3}(), {closure#5}(), EmailTemplateRenderer

### Community 78 - "Community 78"
Cohesion: 0.36
Nodes (8): ComplaintCategory, Behavior, Delay, Fraud, GdprAccess, GdprErasure, Rates, Transparency

### Community 79 - "Community 79"
Cohesion: 0.33
Nodes (7): UserRole, ADMIN, INSPECTOR, QUALITY, SOS, SUPER_ADMIN, USER

### Community 81 - "Community 81"
Cohesion: 0.27
Nodes (3): CreateUser, UserForm, UserResource

### Community 84 - "Community 84"
Cohesion: 0.22
Nodes (3): ListOamCodes, ListOrganizations, {closure#3}()

### Community 85 - "Community 85"
Cohesion: 0.22
Nodes (5): {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}()

### Community 87 - "Community 87"
Cohesion: 0.43
Nodes (5): AmlReportStatus, ARCHIVED, DRAFTED, EVALUATING, REPORTED

### Community 88 - "Community 88"
Cohesion: 0.61
Nodes (6): AuditStatus, CANCELLED, COMPLETED, FOLLOW_UP, IN_PROGRESS, PLANNED

### Community 89 - "Community 89"
Cohesion: 0.61
Nodes (6): ComplaintStatus, Accepted, Escalated, Investigating, Open, Rejected

### Community 90 - "Community 90"
Cohesion: 0.61
Nodes (6): FindingStatus, AcceptedRisk, Closed, InProgress, Open, Resolved

### Community 91 - "Community 91"
Cohesion: 0.68
Nodes (5): SyncStatus, FAILED, LOCAL, SYNCED, SYNCING

### Community 93 - "Community 93"
Cohesion: 0.48
Nodes (5): ComplaintMacroCategory, Financial, Insurance, Operational, Privacy

### Community 94 - "Community 94"
Cohesion: 0.67
Nodes (5): FindingSeverity, Critical, Major, Minor, Observation

### Community 95 - "Community 95"
Cohesion: 0.48
Nodes (5): GdprDsrStatus, EXTENDED, FULFILLED, PENDING, REJECTED

### Community 96 - "Community 96"
Cohesion: 0.48
Nodes (5): ReceptionChannel, BreviManu, Email, Pec, Raccomandata

### Community 97 - "Community 97"
Cohesion: 0.48
Nodes (5): RegulatoryFramework, GDPR, IVASS, OAM, SAFETY

### Community 101 - "Community 101"
Cohesion: 0.53
Nodes (4): GdprBreachStatus, CLOSED, CONTAINED, INVESTIGATING

## Knowledge Gaps
- **8 isolated node(s):** `Base`, `Medium`, `Full`, `Ok`, `Regular` (+3 more)
  These have ≤1 connection - possible missing edges. (Counts symbols only; 406 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **34 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `DocumentStatus` connect `Community 52` to `Community 39`, `Document Import Actions`, `Community 75`, `Community 77`, `Community 19`, `Community 55`?**
  _High betweenness centrality (0.067) - this node is a cross-community bridge._
- **What connects `Base`, `Medium`, `Full` to the rest of the system?**
  _8 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Audit Finding Pages` be split into smaller, more focused modules?**
  _Cohesion score 0.06093189964157706 - nodes in this community are weakly interconnected._
- **Why does `DynamicGroupExport` connect `Community 28` to `Community 33`, `Community 65`, `Community 66`, `Community 34`, `Community 67`, `Filament Table Actions`, `Document Import Actions`, `Dynamic Group Export`, `Community 72`, `Community 15`, `Community 80`, `Community 18`, `Community 19`, `Community 54`, `Community 24`, `Community 59`, `Community 63`?**
  _High betweenness centrality (0.060) - this node is a cross-community bridge._
- **Should `OAM Practice Import` be split into smaller, more focused modules?**
  _Cohesion score 0.06509803921568627 - nodes in this community are weakly interconnected._
- **Why does `Document` connect `Community 38` to `Branch & Auditable Models`, `Client Blacklist Models`, `Client Model`, `Filament Table Actions`, `Document Import Actions`, `Community 14`, `Community 20`, `Community 29`, `Community 35`, `Community 37`, `Community 39`, `Community 48`, `Community 51`, `Community 54`, `Community 74`, `Community 75`, `Community 80`, `Community 100`, `Community 107`?**
  _High betweenness centrality (0.051) - this node is a cross-community bridge._
- **Should `Branch & Auditable Models` be split into smaller, more focused modules?**
  _Cohesion score 0.06693877551020408 - nodes in this community are weakly interconnected._