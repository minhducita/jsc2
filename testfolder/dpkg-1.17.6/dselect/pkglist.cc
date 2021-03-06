/*
 * dselect - Debian package maintenance user interface
 * pkglist.cc - package list administration
 *
 * Copyright © 1995 Ian Jackson <ian@chiark.greenend.org.uk>
 * Copyright © 2001 Wichert Akkerman <wakkerma@debian.org>
 *
 * This is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

#include <config.h>
#include <compat.h>

#include <assert.h>
#include <errno.h>
#include <string.h>
#include <stdlib.h>
#include <stdio.h>

#include <dpkg/i18n.h>
#include <dpkg/dpkg.h>
#include <dpkg/dpkg-db.h>
#include <dpkg/string.h>

#include "dselect.h"
#include "bindings.h"

int packagelist::compareentries(const struct perpackagestate *a,
                                const struct perpackagestate *b) {
  switch (statsortorder) {
  case sso_avail:
    if (a->ssavail != b->ssavail) return a->ssavail - b->ssavail;
    break;
  case sso_state:
    if (a->ssstate != b->ssstate) return a->ssstate - b->ssstate;
    break;
  case sso_unsorted:
    break;
  default:
    internerr("unknown statsortorder %d", statsortorder);
  }

  const char *asection= a->pkg->section;
  if (!asection && a->pkg->set->name)
    asection = "";
  const char *bsection= b->pkg->section;
  if (!bsection && b->pkg->set->name)
    bsection = "";
  int c_section=
    !asection || !bsection ?
      (!bsection) - (!asection) :
    !*asection || !*bsection ?
      (!*asection) - (!*bsection) :
    strcasecmp(asection,bsection);
  int c_priority=
    a->pkg->priority - b->pkg->priority;
  if (!c_priority && a->pkg->priority == pkginfo::pri_other)
    c_priority= strcasecmp(a->pkg->otherpriority, b->pkg->otherpriority);
  int c_name=
    a->pkg->set->name && b->pkg->set->name ?
      strcasecmp(a->pkg->set->name, b->pkg->set->name) :
    (!b->pkg->set->name) - (!a->pkg->set->name);

  switch (sortorder) {
  case so_section:
    return c_section ? c_section : c_priority ? c_priority : c_name;
  case so_priority:
    return c_priority ? c_priority : c_section ? c_section : c_name;
  case so_alpha:
    return c_name;
  case so_unsorted:
  default:
    internerr("unsorted or unknown sort %d", sortorder);
  }
  /* never reached, make gcc happy */
  return 1;
}

void packagelist::discardheadings() {
  int a,b;
  for (a=0, b=0; a<nitems; a++) {
    if (table[a]->pkg->set->name) {
      table[b++]= table[a];
    }
  }
  nitems= b;

  struct perpackagestate *head, *next;
  head= headings;
  while (head) {
    next= head->uprec;
    delete head->pkg->set;
    delete head;
    head= next;
  }
  headings = nullptr;
}

void packagelist::addheading(enum ssavailval ssavail,
                             enum ssstateval ssstate,
                             pkginfo::pkgpriority priority,
                             const char *otherpriority,
                             const char *section) {
  assert(nitems <= nallocated);
  if (nitems == nallocated) {
    nallocated += nallocated+50;
    struct perpackagestate **newtable= new struct perpackagestate*[nallocated];
    memcpy(newtable, table, nallocated * sizeof(struct perpackagestate *));
    delete[] table;
    table= newtable;
  }

  debug(dbg_general, "packagelist[%p]::addheading(%d,%d,%d,%s,%s)",
        this, ssavail, ssstate, priority,
        otherpriority ? otherpriority : "<null>",
        section ? section : "<null>");

  struct pkgset *newset = new pkgset;
  newset->name = nullptr;
  struct pkginfo *newhead = &newset->pkg;
  newhead->set = newset;
  newhead->priority= priority;
  newhead->otherpriority= otherpriority;
  newhead->section= section;

  struct perpackagestate *newstate= new perpackagestate;
  newstate->pkg= newhead;
  newstate->uprec= headings;
  headings= newstate;
  newstate->ssavail= ssavail;
  newstate->ssstate= ssstate;
  newhead->clientdata= newstate;

  table[nitems++]= newstate;
}

static packagelist *sortpackagelist;

int qsort_compareentries(const void *a, const void *b) {
  return sortpackagelist->compareentries(*(const struct perpackagestate **)a,
                                         *(const struct perpackagestate **)b);
}

void packagelist::sortinplace() {
  sortpackagelist= this;

  debug(dbg_general, "packagelist[%p]::sortinplace()", this);
  qsort(table, nitems, sizeof(struct pkgbin *), qsort_compareentries);
}

void packagelist::ensurestatsortinfo() {
  const struct dpkg_version *veri;
  const struct dpkg_version *vera;
  struct pkginfo *pkg;
  int index;

  debug(dbg_general,
        "packagelist[%p]::ensurestatsortinfos() sortorder=%d nitems=%d",
        this, statsortorder, nitems);

  switch (statsortorder) {
  case sso_unsorted:
    debug(dbg_general, "packagelist[%p]::ensurestatsortinfos() unsorted", this);
    return;
  case sso_avail:
    debug(dbg_general, "packagelist[%p]::ensurestatsortinfos() calcssadone=%d",
          this, calcssadone);
    if (calcssadone) return;
    for (index=0; index < nitems; index++) {
      debug(dbg_general, "packagelist[%p]::ensurestatsortinfos() i=%d pkg=%s",
            this, index, pkg_name(table[index]->pkg, pnaw_always));
      pkg= table[index]->pkg;
      switch (pkg->status) {
      case pkginfo::stat_unpacked:
      case pkginfo::stat_halfconfigured:
      case pkginfo::stat_halfinstalled:
      case pkginfo::stat_triggersawaited:
      case pkginfo::stat_triggerspending:
        table[index]->ssavail= ssa_broken;
        break;
      case pkginfo::stat_notinstalled:
      case pkginfo::stat_configfiles:
        if (!dpkg_version_is_informative(&pkg->available.version)) {
          table[index]->ssavail= ssa_notinst_gone;
// FIXME: Disable for now as a workaround, until dselect knows how to properly
//        store seen packages.
#if 0
        } else if (table[index]->original == pkginfo::want_unknown) {
          table[index]->ssavail= ssa_notinst_unseen;
#endif
        } else {
          table[index]->ssavail= ssa_notinst_seen;
        }
        break;
      case pkginfo::stat_installed:
        veri= &table[index]->pkg->installed.version;
        vera= &table[index]->pkg->available.version;
        if (!dpkg_version_is_informative(vera)) {
          table[index]->ssavail= ssa_installed_gone;
        } else if (dpkg_version_compare(vera, veri) > 0) {
          table[index]->ssavail= ssa_installed_newer;
        } else {
          table[index]->ssavail= ssa_installed_sameold;
        }
        break;
      default:
        internerr("unknown status %d on sso_avail", pkg->status);
      }
      debug(dbg_general,
            "packagelist[%p]::ensurestatsortinfos() i=%d ssavail=%d",
            this, index, table[index]->ssavail);
    }
    calcssadone = true;
    break;
  case sso_state:
    debug(dbg_general, "packagelist[%p]::ensurestatsortinfos() calcsssdone=%d",
          this, calcsssdone);
    if (calcsssdone) return;
    for (index=0; index < nitems; index++) {
      debug(dbg_general, "packagelist[%p]::ensurestatsortinfos() i=%d pkg=%s",
            this, index, pkg_name(table[index]->pkg, pnaw_always));
      switch (table[index]->pkg->status) {
      case pkginfo::stat_unpacked:
      case pkginfo::stat_halfconfigured:
      case pkginfo::stat_halfinstalled:
      case pkginfo::stat_triggersawaited:
      case pkginfo::stat_triggerspending:
        table[index]->ssstate= sss_broken;
        break;
      case pkginfo::stat_notinstalled:
        table[index]->ssstate= sss_notinstalled;
        break;
      case pkginfo::stat_configfiles:
        table[index]->ssstate= sss_configfiles;
        break;
      case pkginfo::stat_installed:
        table[index]->ssstate= sss_installed;
        break;
      default:
        internerr("unknown status %d on sso_state", table[index]->pkg->status);
      }
      debug(dbg_general,
            "packagelist[%p]::ensurestatsortinfos() i=%d ssstate=%d",
            this, index, table[index]->ssstate);
    }
    calcsssdone = true;
    break;
  default:
    internerr("unknown statsortorder %d", statsortorder);
  }
}

void packagelist::sortmakeheads() {
  discardheadings();
  ensurestatsortinfo();
  sortinplace();
  assert(nitems);

  debug(dbg_general,
        "packagelist[%p]::sortmakeheads() sortorder=%d statsortorder=%d",
        this, sortorder, statsortorder);

  int nrealitems= nitems;
  addheading(ssa_none, sss_none, pkginfo::pri_unset, nullptr, nullptr);

  assert(sortorder != so_unsorted);
  if (sortorder == so_alpha && statsortorder == sso_unsorted) { sortinplace(); return; }

  // Important: do not save pointers into table in this function, because
  // addheading may need to reallocate table to make it larger !

  struct pkginfo *lastpkg;
  struct pkginfo *thispkg;
  lastpkg = nullptr;
  int a;
  for (a=0; a<nrealitems; a++) {
    thispkg= table[a]->pkg;
    assert(thispkg->set->name);
    int ssdiff= 0;
    ssavailval ssavail= ssa_none;
    ssstateval ssstate= sss_none;
    switch (statsortorder) {
    case sso_avail:
      ssavail= thispkg->clientdata->ssavail;
      ssdiff= (!lastpkg || ssavail != lastpkg->clientdata->ssavail);
      break;
    case sso_state:
      ssstate= thispkg->clientdata->ssstate;
      ssdiff= (!lastpkg || ssstate != lastpkg->clientdata->ssstate);
      break;
    case sso_unsorted:
      break;
    default:
      internerr("unknown statsortorder %d", statsortorder);
    }

    int prioritydiff= (!lastpkg ||
                       thispkg->priority != lastpkg->priority ||
                       (thispkg->priority == pkginfo::pri_other &&
                        strcasecmp(thispkg->otherpriority,lastpkg->otherpriority)));
    int sectiondiff= (!lastpkg ||
                      strcasecmp(thispkg->section ? thispkg->section : "",
                                 lastpkg->section ? lastpkg->section : ""));

    debug(dbg_general,
          "packagelist[%p]::sortmakeheads() pkg=%s  state=%d avail=%d %s  "
          "priority=%d otherpriority=%s %s  section=%s %s",
          this, pkg_name(thispkg, pnaw_always),
          thispkg->clientdata->ssavail, thispkg->clientdata->ssstate,
          ssdiff ? "*diff" : "same",
          thispkg->priority,
          thispkg->priority != pkginfo::pri_other ? "<none>" :
          thispkg->otherpriority ? thispkg->otherpriority : "<null>",
          prioritydiff ? "*diff*" : "same",
          thispkg->section ? thispkg->section : "<null>",
          sectiondiff ? "*diff*" : "same");

    if (ssdiff)
      addheading(ssavail,ssstate,
                 pkginfo::pri_unset, nullptr, nullptr);

    if (sortorder == so_section && sectiondiff)
      addheading(ssavail,ssstate,
                 pkginfo::pri_unset, nullptr,
                 thispkg->section ? thispkg->section : "");

    if (sortorder == so_priority && prioritydiff)
      addheading(ssavail,ssstate,
                 thispkg->priority, thispkg->otherpriority, nullptr);

    if (sortorder != so_alpha && (prioritydiff || sectiondiff))
      addheading(ssavail,ssstate,
                 thispkg->priority,thispkg->otherpriority,
                 thispkg->section ? thispkg->section : "");

    lastpkg= thispkg;
  }

  if (listpad) {
    werase(listpad);
  }

  sortinplace();
}

void packagelist::initialsetup() {
  debug(dbg_general, "packagelist[%p]::initialsetup()", this);

  int allpackages = pkg_db_count_pkg();
  datatable= new struct perpackagestate[allpackages];

  nallocated= allpackages+150; // will realloc if necessary, so 150 not critical
  table= new struct perpackagestate*[nallocated];

  depsdone = nullptr;
  unavdone = nullptr;
  currentinfo = nullptr;
  headings = nullptr;
  verbose = false;
  calcssadone = calcsssdone = false;
  searchdescr = false;
}

void packagelist::finalsetup() {
  setcursor(0);

  debug(dbg_general, "packagelist[%p]::finalsetup done; recursive=%d nitems=%d",
        this, recursive, nitems);
}

packagelist::packagelist(keybindings *kb) : baselist(kb) {
  // nonrecursive
  initialsetup();
  struct pkgiterator *iter;
  struct pkginfo *pkg;

  nitems = 0;

  iter = pkg_db_iter_new();
  while ((pkg = pkg_db_iter_next_pkg(iter))) {
    struct perpackagestate *state= &datatable[nitems];
    state->pkg= pkg;
    if (pkg->status == pkginfo::stat_notinstalled &&
        !pkg->files &&
        pkg->want != pkginfo::want_install) {
      pkg->clientdata = nullptr;
      continue;
    }
    // treat all unknown packages as already seen
    state->direct= state->original= (pkg->want == pkginfo::want_unknown ? pkginfo::want_purge : pkg->want);
    if (modstatdb_get_status() == msdbrw_write &&
        state->original == pkginfo::want_unknown) {
      state->suggested=
        pkg->status == pkginfo::stat_installed ||
          pkg->priority <= pkginfo::pri_standard /* FIXME: configurable */
            ? pkginfo::want_install : pkginfo::want_purge;
      state->spriority= sp_inherit;
    } else {
      state->suggested= state->original;
      state->spriority= sp_fixed;
    }
    state->dpriority= dp_must;
    state->selected= state->suggested;
    state->uprec = nullptr;
    state->relations.init();
    pkg->clientdata= state;
    table[nitems]= state;
    nitems++;
  }
  pkg_db_iter_free(iter);

  if (!nitems)
    ohshit(_("there are no packages"));
  recursive = false;
  sortorder= so_priority;
  statsortorder= sso_avail;
  versiondisplayopt= vdo_both;
  sortmakeheads();
  finalsetup();
}

packagelist::packagelist(keybindings *kb, pkginfo **pkgltab) : baselist(kb) {
  // takes over responsibility for pkgltab (recursive)
  initialsetup();

  recursive = true;
  nitems= 0;
  if (pkgltab) {
    add(pkgltab);
    delete[] pkgltab;
  }

  sortorder= so_unsorted;
  statsortorder= sso_unsorted;
  versiondisplayopt= vdo_none;
  finalsetup();
}

void
perpackagestate::free(bool recursive)
{
  if (pkg->set->name) {
    if (modstatdb_get_status() == msdbrw_write) {
      if (uprec) {
        assert(recursive);
        uprec->selected= selected;
        pkg->clientdata= uprec;
      } else {
        assert(!recursive);
        if (pkg->want != selected &&
            !(pkg->want == pkginfo::want_unknown && selected == pkginfo::want_purge)) {
          pkg->want= selected;
        }
        pkg->clientdata = nullptr;
      }
    }
    relations.destroy();
  }
}

packagelist::~packagelist() {
  debug(dbg_general, "packagelist[%p]::~packagelist()", this);

  if (searchstring[0])
    regfree(&searchfsm);

  discardheadings();

  int index;
  for (index=0; index<nitems; index++) table[index]->free(recursive);
  delete[] table;
  delete[] datatable;
  debug(dbg_general, "packagelist[%p]::~packagelist() tables freed", this);

  doneent *search, *next;
  for (search=depsdone; search; search=next) {
    next= search->next;
    delete search;
  }

  debug(dbg_general, "packagelist[%p]::~packagelist() done", this);
}

bool
packagelist::checksearch(char *rx)
{
  int rc, opt = REG_NOSUB;
  int pos;

  if (str_is_unset(rx))
    return false;

  searchdescr = false;
  if (searchstring[0]) {
    regfree(&searchfsm);
    searchstring[0]=0;
  }

  /* look for search options */
  for (pos = strlen(rx) - 1; pos >= 0; pos--)
    if ((rx[pos] == '/') && ((pos == 0) || (rx[pos - 1] != '\\')))
      break;

  if (pos >= 0) {
    rx[pos++] = '\0';
    if (strcspn(rx + pos, "di") != 0) {
      displayerror(_("invalid search option given"));
      return false;
    }

   while (rx[pos]) {
     if (rx[pos] == 'i')
       opt|=REG_ICASE;
     else if (rx[pos] == 'd')
       searchdescr = true;
     pos++;
   }
  }

  rc = regcomp(&searchfsm, rx, opt);
  if (rc != 0) {
    displayerror(_("error in regular expression"));
    return false;
  }

  return true;
}

bool
packagelist::matchsearch(int index)
{
  const char *name;

  name = itemname(index);
  if (!name)
    return false;	/* Skip things without a name (seperators) */

  if (regexec(&searchfsm, name, 0, nullptr, 0) == 0)
    return true;

  if (searchdescr) {
    const char *descr = table[index]->pkg->available.description;
    if (str_is_unset(descr))
      return false;

    if (regexec(&searchfsm, descr, 0, nullptr, 0) == 0)
      return true;
  }

  return false;
}

pkginfo **packagelist::display() {
  // returns list of packages as null-terminated array, which becomes owned
  // by the caller, if a recursive check is desired.
  // returns 0 if no recursive check is desired.
  int response, index;
  const keybindings::interpretation *interp;
  pkginfo **retl;

  debug(dbg_general, "packagelist[%p]::display()", this);

  setupsigwinch();
  startdisplay();

  if (!expertmode)
  displayhelp(helpmenulist(),'i');

  debug(dbg_general, "packagelist[%p]::display() entering loop", this);
  for (;;) {
    if (whatinfo_height) wcursyncup(whatinfowin);
    if (doupdate() == ERR)
      ohshite(_("doupdate failed"));
    signallist= this;
    if (sigprocmask(SIG_UNBLOCK, &sigwinchset, nullptr))
      ohshite(_("failed to unblock SIGWINCH"));
    do
    response= getch();
    while (response == ERR && errno == EINTR);
    if (sigprocmask(SIG_BLOCK, &sigwinchset, nullptr))
      ohshite(_("failed to re-block SIGWINCH"));
    if (response == ERR)
      ohshite(_("getch failed"));
    interp= (*bindings)(response);
    debug(dbg_general, "packagelist[%p]::display() response=%d interp=%s",
          this, response, interp ? interp->action : "[none]");
    if (!interp) { beep(); continue; }
    (this->*(interp->pfn))();
    if (interp->qa != qa_noquit) break;
  }
  pop_cleanup(ehflag_normaltidy); // unset the SIGWINCH handler
  enddisplay();

  if (interp->qa == qa_quitnochecksave ||
      modstatdb_get_status() == msdbrw_readonly) {
    debug(dbg_general, "packagelist[%p]::display() done - quitNOcheck", this);
    return nullptr;
  }

  if (recursive) {
    retl= new pkginfo*[nitems+1];
    for (index=0; index<nitems; index++) retl[index]= table[index]->pkg;
    retl[nitems] = nullptr;
    debug(dbg_general, "packagelist[%p]::display() done, retl=%p", this, retl);
    return retl;
  } else {
    packagelist *sub = new packagelist(bindings, nullptr);
    for (index=0; index < nitems; index++)
      if (table[index]->pkg->set->name)
        sub->add(table[index]->pkg);
    repeatedlydisplay(sub,dp_must);
    debug(dbg_general,
          "packagelist[%p]::display() done, not recursive no retl", this);
    return nullptr;
  }
}
