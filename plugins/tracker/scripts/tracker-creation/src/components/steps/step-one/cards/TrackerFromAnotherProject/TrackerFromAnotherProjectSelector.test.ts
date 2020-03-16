/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { ProjectWithTrackers, State, Tracker } from "../../../../../store/type";
import { shallowMount, Wrapper } from "@vue/test-utils";
import { createStoreMock } from "../../../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import { createTrackerCreationLocalVue } from "../../../../../helpers/local-vue-for-tests";
import TrackerFromAnotherProjectSelector from "./TrackerFromAnotherProjectSelector.vue";

describe("TrackerFromAnotherProject", () => {
    async function getWrapper(
        state: State = {} as State
    ): Promise<Wrapper<TrackerFromAnotherProjectSelector>> {
        return shallowMount(TrackerFromAnotherProjectSelector, {
            mocks: {
                $store: createStoreMock({
                    state
                })
            },
            localVue: await createTrackerCreationLocalVue()
        });
    }

    it("fills the tracker select options with the trackers of the selected project", async () => {
        const bugs_tracker: Tracker = { id: "104", name: "Bugs", tlp_color: "peggy-pink" };
        const helpdesk_project: ProjectWithTrackers = {
            id: "101",
            name: "Helpdesk",
            trackers: [{ id: "103", name: "SLA", tlp_color: "peggy-pink" }, bugs_tracker]
        };

        const wrapper = await getWrapper({
            selected_project_tracker_template: null,
            selected_project: null,
            trackers_from_other_projects: [
                helpdesk_project,
                {
                    id: "102",
                    name: "Scrum",
                    trackers: [
                        { id: "105", name: "Sprints", tlp_color: "peggy-pink" },
                        { id: "106", name: "User stories", tlp_color: "peggy-pink" }
                    ]
                }
            ]
        } as State);

        const project_select = wrapper.find("[data-test=project-select]");

        project_select
            .findAll("option")
            .at(1)
            .setSelected();

        const tracker_select = wrapper.find("[data-test=project-tracker-select]");
        const tracker_option_names = tracker_select
            .findAll("option")
            .wrappers.map(wrapper => wrapper.text());

        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
            "setSelectedProject",
            helpdesk_project
        );

        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
            "setSelectedProjectTrackerTemplate",
            null
        );

        expect(tracker_option_names).toContain("SLA");
        expect(tracker_option_names).toContain("Bugs");
    });

    it("Sets the selected tracker in store when a new one is selected", async () => {
        const bugs_tracker: Tracker = { id: "104", name: "Bugs", tlp_color: "peggy-pink" };
        const sla_tracker = { id: "103", name: "SLA", tlp_color: "peggy-pink" };
        const helpdesk_project: ProjectWithTrackers = {
            id: "101",
            name: "Helpdesk",
            trackers: [sla_tracker, bugs_tracker]
        };

        const wrapper = await getWrapper({
            selected_project_tracker_template: bugs_tracker,
            selected_project: helpdesk_project,
            trackers_from_other_projects: [
                helpdesk_project,
                {
                    id: "102",
                    name: "Scrum",
                    trackers: [
                        { id: "105", name: "Sprints", tlp_color: "peggy-pink" },
                        { id: "106", name: "User stories", tlp_color: "peggy-pink" }
                    ]
                }
            ]
        } as State);

        const tracker_select = wrapper.find("[data-test=project-tracker-select]");

        tracker_select
            .findAll("option")
            .at(1)
            .setSelected();

        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
            "setSelectedProjectTrackerTemplate",
            sla_tracker
        );
    });
});
