# Entities Notification Rules Engine for CiviCRM

## Overview

This project aims to develop a reusable, configurable rules engine for triggering timely and automated notifications within **CiviCRM**. It is designed to ensure that stakeholders (e.g., Case managers) are notified about key lifecycle events such as upcoming recertifications or newly created activities, while minimizing manual effort and reducing the risk of human error.

---

## Business Goals

- **Ensure Timely Communication**  
  Automatically notify relevant contacts when significant events occur within a Case or Activity.

- **Reduce Manual Work and Errors**  
  Standardize communications to minimize omissions and inconsistencies.

- **Provide a Reusable Solution**  
  Design a flexible, modular system that can be extended across different CiviCRM use cases.

---

## Functional Triggers

### 1. **Time-Based Triggers**
- Notify contacts X days before a recertification is due.

### 2. **Event-Based Triggers**
- When a **Case** or **Activity** changes status.
- When a new **To-Do Activity** is created and linked to a Case.

---

## Business User Configuration

A user-friendly interface will be provided through **Afforms**, enabling non-technical users to:

- Create, enable or disable **Rule Sets** and **Notification Rules**.
- Define complex conditions (e.g., before/after values of fields).
- Select recipients using:
  - Contact IDs
  - Groups
  - Contact types
  - Case roles

---

## Roles & Governance

| Role                    | Responsibility                                    |
|-------------------------|--------------------------------------------------|
| **Product Owner**       | Prioritize rules and approve message content.    |
| **Development Team**    | Build and maintain the engine and UI.            |
| **Functional Admins**   | Manage rules in production.                      |
| **Support Team**        | Handle incidents and change requests.            |

---

## Extensibility

The engine is designed to be easily extended to support additional entities and use cases **without requiring changes to the core model**.

---

## Operations & Support

- All notifications will be **logged with traceability**.
- Logs will be **accessible for troubleshooting** without code-level access.

---

## Performance & Scalability

- Capable of processing large batches efficiently.
- Designed to **avoid overload of the CiviCRM email queue**.

---

## Security & Compliance

- UI access will be controlled via **CiviCRM permission management**.

---

## Quality Assurance

- Each scenario will be covered by **functional test cases**:
  - Recertification reminders
  - Status change events
  - Activity creation events

---

## Documentation

The repository will include:

- Functional specifications
- Technical architecture
- Setup and configuration guides
- User guide for business administrators
- Contribution and development guidelines

---



This is an [extension for CiviCRM](https://docs.civicrm.org/sysadmin/en/latest/customize/extensions/), licensed under [AGPL-3.0](LICENSE.txt).
